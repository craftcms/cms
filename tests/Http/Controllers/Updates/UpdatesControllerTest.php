<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Updates\UpdatesController;
use CraftCms\Cms\Updates\Data\Update;
use CraftCms\Cms\Updates\Data\UpdateRelease;
use CraftCms\Cms\Updates\Enums\UpdateStatus;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action([UpdatesController::class, 'check']))
        ->assertUnauthorized();
});

it('can check for updates only if cached', function () {
    postJson(action([UpdatesController::class, 'check'], ['onlyIfCached' => true]))
        ->assertJson([
            'cached' => false,
        ]);
});

it('can check for updates without cache', function () {
    postJson(action([UpdatesController::class, 'check'], ['onlyIfCached' => false]))
        ->assertJson([
            'total' => 0,
            'critical' => false,
            'allowUpdates' => true,
        ]);
});

it('can include details', function () {
    postJson(action([UpdatesController::class, 'check'], ['includeDetails' => true]))
        ->assertJson([
            'total' => 0,
            'critical' => false,
            'allowUpdates' => true,
            'updates' => [
                'cms' => [
                    'status' => UpdateStatus::ELIGIBLE->value,
                ],
                'plugins' => [],
            ],
        ]);
});

it('can cache new update info', function () {
    loadTestPlugin();

    postJson(action([UpdatesController::class, 'cache'], [
        'updates' => [
            'cms' => new Update(packageName: 'craftcms/cms')->toArray(),
            'plugins' => [
                'test-plugin' => new Update(
                    packageName: 'craftcms/test-plugin',
                    releases: [
                        new UpdateRelease('1.0.1'),
                    ]
                )->toArray(),
            ],
        ],
    ]))->assertOk();

    postJson(action([UpdatesController::class, 'check'], ['includeDetails' => true]))
        ->assertJson([
            'total' => 1,
            'critical' => false,
            'allowUpdates' => true,
            'updates' => [
                'cms' => [
                    'status' => UpdateStatus::ELIGIBLE->value,
                ],
                'plugins' => [
                    ['packageName' => 'craftcms/test-plugin'],
                ],
            ],
        ]);
});
