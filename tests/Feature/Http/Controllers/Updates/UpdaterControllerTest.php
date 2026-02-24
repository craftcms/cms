<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->hashedData = Crypt::encrypt(Json::encode([
        'install' => [
            'craft' => '6.0.0',
        ],
        'packageNames' => [
            'craft' => 'Craft CMS',
        ],
    ]));
});

dataset('routes', [
    [UpdaterController::class, 'index'],
    [UpdaterController::class, 'forceUpdate'],
    [UpdaterController::class, 'backup'],
    [UpdaterController::class, 'serverCheck'],
    [UpdaterController::class, 'revert'],
    [UpdaterController::class, 'migrate'],
    [UpdaterController::class, 'precheck'],
    [UpdaterController::class, 'recheckComposer'],
    [UpdaterController::class, 'composerInstall'],
    [UpdaterController::class, 'composerRemove'],
    [UpdaterController::class, 'finish'],
]);

test('all routes validate data', function (string $controller, string $action) {
    if ($action === 'index') {
        postJson(action([$controller, $action]), [
            'install' => [],
        ])->assertJsonValidationErrors([
            'packageNames',
        ]);

        return;
    }

    postJson(action([$controller, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('index returns Inertia Updater page', function () {
    post(action([UpdaterController::class, 'index']), [
        'install' => [
            'craft' => '100.0.0',
        ],
        'packageNames' => [
            'craft' => 'craftcms/cms',
        ],
    ])
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Updater')
            ->has('title')
            ->has('initialState')
            ->has('actionPrefix')
            ->has('returnUrl')
        );
});
