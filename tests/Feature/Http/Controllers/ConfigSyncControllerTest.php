<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\swap;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->hashedData = Crypt::encrypt(Json::encode([

    ]));

    // Reset anything that could have changed project config
    app(ProjectConfig::class)->rebuild();
});

dataset('routes', [
    'index',
    'retry',
    'applyYamlChanges',
    'regenerateYaml',
    'uninstallPlugin',
    'installPlugin',
    'precheck',
    'recheckComposer',
    'composerInstall',
    'composerRemove',
    'finish',
]);

it('uses normal CP routes', function (string $action) {
    expect(parse_url(action([ConfigSyncController::class, $action]), PHP_URL_PATH))
        ->toStartWith(parse_url(cp_url('config-sync'), PHP_URL_PATH));
})->with('routes');

it('requires authentication all routes', function (string $action) {
    auth()->logout();

    postJson(action([ConfigSyncController::class, $action]))->assertUnauthorized();
})->with('routes');

test('all routes validate data', function (string $action) {
    if ($action === 'index') {
        post(action([ConfigSyncController::class, $action]))->assertOk();

        return;
    }

    postJson(action([ConfigSyncController::class, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('index returns Inertia Updater page', function () {
    post(action([ConfigSyncController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('updater/Index')
            ->has('title')
            ->has('initialState')
            ->where('initialState.finishUrl', action([ConfigSyncController::class, 'finish']))
            ->has('returnUrl')
        );
});

test('retry', function () {
    postJson(action([ConfigSyncController::class, 'retry']), [
        'data' => $this->hashedData,
    ])
        ->assertOk()
        ->assertSee('Applying changes from the project config YAML files');
});

test('apply-yaml-changes', function () {
    postJson(action([ConfigSyncController::class, 'applyYamlChanges']), [
        'data' => $this->hashedData,
    ])
        ->assertOk()
        ->assertSee('All done!')
        ->assertSee('dashboard'); // return url
});

test('regenerate-yaml', function () {
    postJson(action([ConfigSyncController::class, 'regenerateYaml']), [
        'data' => $this->hashedData,
    ])
        ->assertOk()
        ->assertSee('All done!')
        ->assertSee('dashboard'); // return url
});

test('uninstall-plugin', function () {
    Cache::put('test-uninstalled-plugin', []);

    swap(Plugins::class, app(PluginsFake::class));

    postJson(action([ConfigSyncController::class, 'uninstallPlugin']), [
        'data' => Crypt::encrypt(Json::encode([
            'uninstallPlugins' => [
                'test-plugin',
            ],
        ])),
    ])
        ->assertOk()
        ->assertSee('Applying changes from the project config YAML files');

    expect(Cache::get('test-uninstalled-plugin'))->toBe('test-plugin');
});

test('install-plugin', function () {
    Cache::put('test-installed-plugin', []);

    swap(Plugins::class, app(PluginsFake::class));

    postJson(action([ConfigSyncController::class, 'installPlugin']), [
        'data' => Crypt::encrypt(Json::encode([
            'installPlugins' => [
                'test-plugin',
            ],
        ])),
    ])
        ->assertOk()
        ->assertSee('Applying changes from the project config YAML files');

    expect(Cache::get('test-installed-plugin'))->toBe('test-plugin');
});

class PluginsFake extends Plugins
{
    #[Override]
    public function installPlugin(string $handle, ?string $edition = null): bool
    {
        Cache::put('test-installed-plugin', $handle);

        return true;
    }

    #[Override]
    public function uninstallPlugin(string $handle, bool $force = false): bool
    {
        Cache::put('test-uninstalled-plugin', $handle);

        return true;
    }
}
