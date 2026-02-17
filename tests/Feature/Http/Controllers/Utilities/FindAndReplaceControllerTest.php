<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\FindAndReplace as FindAndReplaceUtility;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access find and replace utility', function () {
    Cms::config()->disabledUtilities = [FindAndReplaceUtility::id()];

    postJson(action(FindAndReplaceController::class), [
        'params' => [
            'find' => 'oldtext',
            'replace' => 'newtext',
        ],
    ])
        ->assertForbidden();
});

test('can queue a find and replace job', function () {
    $params = [
        'params' => [
            'find' => 'oldtext',
            'replace' => 'newtext',
        ],
    ];

    postJson(action(FindAndReplaceController::class), $params)
        ->assertOk();
});

test('requires find and replace parameters', function () {
    postJson(action(FindAndReplaceController::class), [
        'params' => [],
    ])
        ->assertJsonValidationErrors(['params.find', 'params.replace']);
});
