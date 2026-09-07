<?php

declare(strict_types=1);

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\FluentTestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPluginSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $plugins = configurationTestPlugins();
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->writeYamlAutomatically = false;

    expect($plugins->installPlugin('fluent-test-plugin'))->toBeTrue();
    $this->assertDatabaseHas(Table::PLUGINS, [
        'handle' => 'fluent-test-plugin',
        'version' => '1.0.0',
        'schemaVersion' => '1.0.0',
    ]);
    expect($plugins->savePluginSettings(FluentTestPlugin::getInstance(), [
        'foo' => 'Stored foo',
        'bar' => 'Stored bar',
    ]))->toBeTrue();

    $projectConfig->flush();
    $projectConfig->reset();
    $this->plugins = configurationTestPlugins();
});

function configurationTestPlugins(): Plugins
{
    app()->forgetInstance(Plugins::class);
    app()->offsetUnset(FluentTestPlugin::class);
    $plugins = app(Plugins::class);

    new ReflectionProperty(Plugins::class, 'composerPluginInfo')->setValue($plugins, [
        'fluent-test-plugin' => [
            'class' => FluentTestPlugin::class,
            'name' => 'Fluent Test Plugin',
            'packageName' => 'craftcms/fluent-test-plugin',
            'version' => '1.0.0',
        ],
    ]);

    return $plugins;
}

it('loads installed settings with normalized snapshot or sparse array precedence', function (Closure $makeInput, ?string $expectedBar) {
    $input = $makeInput();
    Config::set('craft.fluent-test-plugin', $input);
    new ConfigServiceProvider(app())->register();
    $snapshot = Config::get('craft.fluent-test-plugin');

    expect($snapshot)->toBeArray()
        ->and($this->plugins->arePluginsLoaded())->toBeFalse();

    $this->plugins->loadPlugins();
    $plugin = $this->plugins->getPlugin('fluent-test-plugin');
    $settings = $plugin->getSettings();

    expect($this->plugins->getStoredPluginInfo('fluent-test-plugin')['settings'])
        ->toBe(['bar' => 'Stored bar', 'foo' => 'Stored foo']);
    expect($plugin)->toBeInstanceOf(FluentTestPlugin::class)
        ->and($plugin->isInstalled)->toBeTrue()
        ->and($plugin->registeredSettings)->toBe(['foo' => 'File foo', 'bar' => $expectedBar])
        ->and($plugin->bootedSettings)->toBe(['foo' => 'File foo', 'bar' => $expectedBar])
        ->and($settings)->toBe(FluentTestPlugin::getInstance()->getSettings())->not->toBe($input);

    $settings->foo = 'Runtime only';

    expect($plugin->getSettings())->toBe($settings)
        ->and(Config::get('craft.fluent-test-plugin'))->toBe($snapshot);
    $this->assertDatabaseHas(Table::PROJECTCONFIG, [
        'path' => 'plugins.fluent-test-plugin.settings.foo',
        'value' => '"Stored foo"',
    ]);
    if ($input instanceof PluginSettings) {
        expect($input->configData())->toBe(['foo' => 'File foo', 'bar' => null]);
    }
})->with([
    'settings-class snapshot' => [fn () => TestPluginSettings::create()->foo('File foo'), null],
    'plugin-static snapshot' => [fn () => FluentTestPlugin::settings()->foo('File foo'), null],
    'sparse array' => [fn () => ['foo' => 'File foo'], 'Stored bar'],
]);

it('normalizes disabled and uninstalled handles without constructing them during normalization or loading', function () {
    expect($this->plugins->disablePlugin('fluent-test-plugin'))->toBeTrue();
    app(ProjectConfig::class)->flush();
    app(ProjectConfig::class)->reset();
    $plugins = configurationTestPlugins();
    app()->beforeResolving(FluentTestPlugin::class, fn () => throw new RuntimeException('Unexpected plugin construction'));

    Config::set('craft.fluent-test-plugin', FluentTestPlugin::settings()->foo('Disabled'));
    Config::set('craft.uninstalled-plugin', TestPluginSettings::create()->foo('Uninstalled'));
    new ConfigServiceProvider(app())->register();
    $plugins->loadPlugins();

    expect($plugins->isPluginDisabled('fluent-test-plugin'))->toBeTrue()
        ->and($plugins->getPlugin('fluent-test-plugin'))->toBeNull()
        ->and($plugins->isPluginInstalled('uninstalled-plugin'))->toBeFalse()
        ->and($plugins->getPlugin('uninstalled-plugin'))->toBeNull()
        ->and(app()->bound(FluentTestPlugin::class))->toBeFalse()
        ->and(Config::get('craft.fluent-test-plugin'))->toBe(['foo' => 'Disabled', 'bar' => null])
        ->and(Config::get('craft.uninstalled-plugin'))->toBe(['foo' => 'Uninstalled', 'bar' => null]);
    $this->assertDatabaseHas(Table::PROJECTCONFIG, [
        'path' => 'plugins.fluent-test-plugin.enabled',
        'value' => 'false',
    ]);
    $this->assertDatabaseMissing(Table::PLUGINS, ['handle' => 'uninstalled-plugin']);
});

it('flushes submitted overrides and omitted configuration values to the database on the test connection', function () {
    $input = FluentTestPlugin::settings()->foo('File foo')->bar('File bar');
    Config::set('craft.fluent-test-plugin', $input);
    new ConfigServiceProvider(app())->register();
    $plugin = $this->plugins->getPlugin('fluent-test-plugin');
    $settings = $plugin->getSettings();
    $projectConfig = app(ProjectConfig::class);

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'Submitted']))->toBeTrue();
    expect($plugin->getSettings())->toBe($settings)
        ->and($settings->validationData())->toBe(['foo' => 'Submitted', 'bar' => 'File bar'])
        ->and($projectConfig->get('plugins.fluent-test-plugin.settings'))->toBe(['bar' => 'File bar', 'foo' => 'Submitted']);
    expect(DB::table(Table::PROJECTCONFIG)
        ->whereIn('path', ['plugins.fluent-test-plugin.settings.foo', 'plugins.fluent-test-plugin.settings.bar'])
        ->orderBy('path')->pluck('value', 'path')->all())->toBe([
            'plugins.fluent-test-plugin.settings.bar' => '"Stored bar"',
            'plugins.fluent-test-plugin.settings.foo' => '"Stored foo"',
        ]);

    $projectConfig->flush();

    expect(DB::table(Table::PROJECTCONFIG)
        ->whereIn('path', ['plugins.fluent-test-plugin.settings.foo', 'plugins.fluent-test-plugin.settings.bar'])
        ->orderBy('path')->pluck('value', 'path')->all())->toBe([
            'plugins.fluent-test-plugin.settings.bar' => '"File bar"',
            'plugins.fluent-test-plugin.settings.foo' => '"Submitted"',
        ]);

    $projectConfig->reset();

    expect($projectConfig->get('plugins.fluent-test-plugin.settings'))->toBe(['bar' => 'File bar', 'foo' => 'Submitted'])
        ->and($this->plugins->getStoredPluginInfo('fluent-test-plugin')['settings'])->toBe(['bar' => 'Stored bar', 'foo' => 'Stored foo'])
        ->and($plugin->getSettings())->toBe($settings)
        ->and($settings->foo)->toBe('Submitted')
        ->and(Config::get('craft.fluent-test-plugin'))->toBe(['foo' => 'File foo', 'bar' => 'File bar'])
        ->and($input->configData())->toBe(['foo' => 'File foo', 'bar' => 'File bar']);
});

it('does not write invalid settings to the database when project config is flushed', function () {
    Config::set('craft.fluent-test-plugin', FluentTestPlugin::settings()->foo('File foo')->bar('File bar'));
    new ConfigServiceProvider(app())->register();
    $plugin = $this->plugins->getPlugin('fluent-test-plugin');
    $projectConfig = app(ProjectConfig::class);

    expect($this->plugins->savePluginSettings($plugin, ['foo' => null]))->toBeFalse();
    expect($plugin->getSettings()->foo)->toBeNull()
        ->and($projectConfig->get('plugins.fluent-test-plugin.settings'))->toBe(['bar' => 'Stored bar', 'foo' => 'Stored foo']);

    $projectConfig->flush();

    expect(DB::table(Table::PROJECTCONFIG)
        ->whereIn('path', ['plugins.fluent-test-plugin.settings.foo', 'plugins.fluent-test-plugin.settings.bar'])
        ->orderBy('path')->pluck('value', 'path')->all())->toBe([
            'plugins.fluent-test-plugin.settings.bar' => '"Stored bar"',
            'plugins.fluent-test-plugin.settings.foo' => '"Stored foo"',
        ]);
    expect(Config::get('craft.fluent-test-plugin'))->toBe(['foo' => 'File foo', 'bar' => 'File bar']);
});
