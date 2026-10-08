<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Capabilities\Plugins;
use CraftCms\Cms\Plugin\Plugins as PluginService;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Update\Updates;

use function Pest\Laravel\mock;

beforeEach(function () {
    loadTestPlugin();
});

it('manages plugins through MCP', function () {
    $plugins = app(Plugins::class);
    $listed = $plugins->list();
    $enabled = $plugins->enable('test-plugin')['plugin'];
    $disabled = $plugins->disable('test-plugin')['plugin'];
    $uninstalled = $plugins->uninstall('test-plugin', force: true)['plugin'];
    $installed = $plugins->install('test-plugin')['plugin'];

    expect($listed)->toMatchArray([
        'count' => 1,
        'updatesChecked' => false,
    ])
        ->and($listed['plugins'][0])->toMatchArray([
            'handle' => 'test-plugin',
            'name' => 'Test Plugin',
            'packageName' => 'craftcms/test-plugin',
            'version' => '1.0.1',
            'edition' => 'standard',
            'isInstalled' => true,
            'isEnabled' => false,
        ])
        ->and($enabled['isEnabled'])->toBeTrue()
        ->and($disabled['isEnabled'])->toBeFalse()
        ->and($uninstalled['isInstalled'])->toBeFalse()
        ->and($installed['isInstalled'])->toBeTrue()
        ->and($installed['isEnabled'])->toBeTrue();
});

it('adds missing plugin packages through Composer before installation', function () {
    $composer = mock(Composer::class);
    $composer->shouldReceive('install')
        ->once()
        ->with(['vendor/plugin' => '^2.0']);

    $plugins = mock(PluginService::class);
    $plugins->shouldReceive('getComposerPluginInfo')
        ->once()
        ->with('plugin-handle')
        ->andReturnNull();

    $result = new Plugins(
        $composer,
        app(GeneralConfig::class),
        $plugins,
        app(Updates::class),
    )->install(
        handle: 'plugin-handle',
        edition: 'pro',
        packageName: 'vendor/plugin',
        version: '^2.0',
    );

    expect($result)->toBe([
        'composerInstalled' => true,
        'handle' => 'plugin-handle',
        'packageName' => 'vendor/plugin',
        'restartRequired' => true,
        'instructions' => 'Reconnect to the Craft MCP server so it loads the new Composer dependencies, then make the indicated tool call to complete the Craft plugin installation.',
        'nextToolCall' => [
            'name' => 'plugins.install',
            'arguments' => [
                'handle' => 'plugin-handle',
                'edition' => 'pro',
            ],
        ],
    ]);
});
