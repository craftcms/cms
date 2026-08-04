<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->hashedData = Crypt::encrypt(Json::encode([
        'packageName' => 'craftcms/test-plugin',
        'handle' => 'test-plugin',
        'edition' => 'standard',
        'version' => '1.0.0',
        'licenseKey' => Str::random(24),
    ]));
});

dataset('routes', [
    'index',
    'craftInstall',
    'enable',
    'migrate',
    'precheck',
    'recheckComposer',
    'composerInstall',
    'composerRemove',
    'finish',
]);

it('aborts when allow updates is false', function () {
    Cms::config()->allowUpdates(false);

    postJson(action([InstallController::class, 'index']))->assertForbidden();
});

it('requires authentication, adminChanges and admin for all routes', function (string $action) {
    auth()->logout();

    postJson(action([InstallController::class, $action]))->assertUnauthorized();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::find()->one());

    postJson(action([InstallController::class, $action]))->assertForbidden();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => true]);
    actingAs(User::find()->one());
    Cms::config()->allowAdminChanges(false);

    postJson(action([InstallController::class, $action]))->assertForbidden();
})->with('routes');

test('all routes validate data', function (string $action) {
    if ($action === 'index') {
        postJson(action([InstallController::class, $action]))
            ->assertJsonValidationErrors([
                'packageName',
                'handle',
                'edition',
                'version',
            ]);

        return;
    }

    postJson(action([InstallController::class, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('index', function () {
    postJson(action([InstallController::class, 'index']), [
        'packageName' => 'craftcms/test-plugin',
        'handle' => 'test-plugin',
        'edition' => 'standard',
        'version' => '1.0.0',
    ])
        ->assertSee('Plugin Installer')
        ->assertSee('Craft.updater');
});

test('craftInstall', function () {
    postJson(action([InstallController::class, 'craftInstall']), [
        'data' => $this->hashedData,
    ])
        ->assertSee('craftcms\/test-plugin has been added')
        ->assertSee('Leave it uninstalled')
        ->assertSee('Remove it')
        ->assertSee('Troubleshoot');
});

test('enable', function () {
    loadTestPlugin();

    postJson(action([InstallController::class, 'enable']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'nextAction' => InstallController::ACTION_MIGRATE,
        'status' => 'Updating the plugin…',
    ]);
});

test('migrate', function () {
    loadTestPlugin();

    postJson(action([InstallController::class, 'enable']), [
        'data' => $this->hashedData,
    ]);

    postJson(action([InstallController::class, 'migrate']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'finished' => true,
        'status' => 'All done!',
    ]);
});

test('finish', function () {
    postJson(action([InstallController::class, 'finish']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'finished' => true,
        'returnUrl' => 'plugin-store',
    ]);
});
