<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Updates\UpdatesController;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Update\Data\Update;
use CraftCms\Cms\Update\Data\UpdateRelease;
use CraftCms\Cms\Update\Enums\UpdateStatus;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());
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

it('still reports releases but omits update actions without Composer', function () {
    $this->partialMock(Composer::class)->shouldReceive('getIsAvailable')->andReturnFalse();

    postJson(action([UpdatesController::class, 'cache']), [
        'includeDetails' => true,
        'updates' => [
            'cms' => new Update(packageName: 'craftcms/cms', releases: [new UpdateRelease('100.0.0')])->toArray(),
            'plugins' => [],
        ],
    ])->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('allowUpdates', false)
        ->assertJsonPath('updates.cms.latestVersion', '100.0.0')
        ->assertJsonMissingPath('updates.cms.ctaText');
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
