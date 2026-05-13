<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
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

test('finish returns a control panel return URL', function (?string $returnUrl, string $expected) {
    $returnUrl = $returnUrl === '{cpTrigger}/login'
        ? sprintf('%s/login', Cms::config()->cpTrigger)
        : $returnUrl;

    $data = [];

    if ($returnUrl !== null) {
        $data['returnUrl'] = $returnUrl;
    }

    postJson(action([UpdaterController::class, 'finish']), [
        'data' => Crypt::encrypt(Json::encode($data)),
    ])->assertOk()
        ->assertJsonPath('returnUrl', Url::cpUrl($expected));
})->with([
    'default redirect' => [null, 'dashboard'],
    'control panel path' => ['utilities/updates', 'utilities/updates'],
    'control panel path with trigger' => ['{cpTrigger}/login', 'login'],
]);

test('finish preserves full return URLs', function (string $returnUrl) {
    postJson(action([UpdaterController::class, 'finish']), [
        'data' => Crypt::encrypt(Json::encode([
            'returnUrl' => $returnUrl,
        ])),
    ])->assertOk()
        ->assertJsonPath('returnUrl', $returnUrl);
})->with([
    'absolute URL' => ['https://example.test/admin/login'],
    'root-relative URL' => ['/admin/login'],
]);
