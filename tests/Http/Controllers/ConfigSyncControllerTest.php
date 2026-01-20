<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

use function Pest\Laravel\actingAs;
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
    [ConfigSyncController::class, 'index'],
    [ConfigSyncController::class, 'retry'],
    [ConfigSyncController::class, 'applyYamlChanges'],
    [ConfigSyncController::class, 'regenerateYaml'],
    [ConfigSyncController::class, 'uninstallPlugin'],
    [ConfigSyncController::class, 'installPlugin'],
    [ConfigSyncController::class, 'precheck'],
    [ConfigSyncController::class, 'recheckComposer'],
    [ConfigSyncController::class, 'composerInstall'],
    [ConfigSyncController::class, 'composerRemove'],
    [ConfigSyncController::class, 'finish'],
]);

it('requires authentication all routes', function (string $controller, string $action) {
    auth()->logout();

    postJson(action([$controller, $action]))->assertUnauthorized();
})->with('routes');

test('all routes validate data', function (string $controller, string $action) {
    if ($action === 'index') {
        postJson(action([$controller, $action]))->assertOk();

        return;
    }

    postJson(action([$controller, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('index', function () {
    postJson(action([ConfigSyncController::class, 'index']))
        ->assertOk()
        ->assertSee('Project Config Sync')
        ->assertSee('Applying changes from the project config YAML files');
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
