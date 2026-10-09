<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
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
    'index',
    'forceUpdate',
    'backup',
    'serverCheck',
    'revert',
    'migrate',
    'precheck',
    'recheckComposer',
    'composerInstall',
    'composerRemove',
    'finish',
]);

it('uses normal CP routes', function (string $action) {
    expect(parse_url(action([UpdaterController::class, $action]), PHP_URL_PATH))
        ->toStartWith(parse_url(cp_url('updates'), PHP_URL_PATH));
})->with('routes');

it('allows the index route without authentication', function () {
    auth()->logout();

    post(action([UpdaterController::class, 'index']))->assertOk();
});

test('all routes validate data', function (string $action) {
    if ($action === 'index') {
        postJson(action([UpdaterController::class, $action]), [
            'install' => [],
        ])->assertJsonValidationErrors([
            'packageNames',
        ]);

        return;
    }

    postJson(action([UpdaterController::class, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('finish requires encrypted update data', function () {
    auth()->logout();
    app()->maintenanceMode()->activate([]);

    postJson(action([UpdaterController::class, 'finish']))
        ->assertJsonValidationErrors(['data']);

    get('/')->assertServiceUnavailable();
});

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
            ->component('updater/Index')
            ->has('title')
            ->has('initialState')
            ->where('initialState.nextUrl', action([UpdaterController::class, 'precheck']))
            ->where('initialState.finishUrl', action([UpdaterController::class, 'finish']))
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

it('renders without the navigation while a Craft update is pending', function () {
    // The nav tree is built from sections, volumes and the rest, so a
    // migration that hasn't run yet can leave it querying columns the database
    // doesn't have — on the very screen the update is run from.
    Info::query()->update(['schemaVersion' => '0.0.0.1']);
    // Both caches have to go for the new row to be read: `Info` is memoized in
    // the request Context, and `Updates` memoizes its own answer — either one
    // still warm (as it is once a service provider has asked) leaves the
    // update looking as though it isn't pending.
    Context::forgetHidden('craft.info');
    app()->forgetInstance(Updates::class);

    post(cp_url('updates'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('craft.nav', []));
});
