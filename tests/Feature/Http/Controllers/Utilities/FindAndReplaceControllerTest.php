<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\FindAndReplace as FindAndReplaceUtility;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access find and replace utility', function () {
    Cms::config()->disabledUtilities = [FindAndReplaceUtility::id()];

    postJson(action(FindAndReplaceController::class), [
        'find' => 'oldtext',
        'replace' => 'newtext',
    ])
        ->assertForbidden();
});

test('can queue a find and replace job', function () {
    Queue::fake();

    postJson(action(FindAndReplaceController::class), [
        'find' => 'oldtext',
        'replace' => 'newtext',
    ])
        ->assertRedirectBack();

    Queue::assertPushed(FindAndReplace::class, fn (FindAndReplace $job) => $job->find === 'oldtext' && $job->replace === 'newtext');
});

test('requires find and replace parameters', function () {
    postJson(action(FindAndReplaceController::class), [])
        ->assertJsonValidationErrors(['find', 'replace']);
});
