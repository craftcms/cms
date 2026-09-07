<?php

declare(strict_types=1);

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\FluentTestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\SnapshotPluginSettings;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPluginSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

it('creates fresh plugin configuration without resolving plugins providers or the database', function () {
    DB::shouldReceive('connection')->never();
    foreach ([Plugins::class, ServiceProvider::class, 'db', 'db.connection'] as $service) {
        app()->beforeResolving($service, fn () => throw new RuntimeException("Unexpected resolution: $service"));
    }

    $first = FluentTestPlugin::config()->foo('File');
    $second = FluentTestPlugin::config()->bar('Other');

    expect($first)->toBeInstanceOf(TestPluginSettings::class)->not->toBe($second)
        ->and($first->configData())->toBe(['foo' => 'File', 'bar' => null])
        ->and($second->configData())->toBe(['foo' => null, 'bar' => 'Other'])
        ->and(app()->bound(FluentTestPlugin::class))->toBeFalse();
});

it('dispatches static config factories independently for distinct plugin types', function () {
    $otherPlugin = new class(app()) extends Plugin
    {
        protected static function createSettings(): SnapshotPluginSettings
        {
            return new SnapshotPluginSettings;
        }
    };

    $first = FluentTestPlugin::config()->foo('First');
    $other = $otherPlugin::config();
    $other->title('Other');

    expect($other)->toBeInstanceOf(SnapshotPluginSettings::class)
        ->and($otherPlugin::config()->title)->toBe('Default')
        ->and($first->foo)->toBe('First')
        ->and(FluentTestPlugin::config()->foo)->toBeNull();
});

it('uses the static factory for retained runtime settings and fresh configuration', function () {
    TestPlugin::$useSettings = true;
    $plugin = new TestPlugin(app());
    $settings = $plugin->getSettings();
    $settings->foo('Runtime');

    expect($settings)->toBeInstanceOf(TestPluginSettings::class)
        ->toBe($plugin->getSettings())
        ->and(TestPlugin::config())->not->toBe($settings)
        ->and(TestPlugin::config()->foo)->toBeNull()
        ->and($plugin->getSettings()->foo)->toBe('Runtime');
});

it('keeps plugins without a settings factory valid', function () {
    $plugin = new class(app()) extends Plugin {};

    expect($plugin->getSettings())->toBeNull()
        ->and($plugin->getSettings())->toBeNull();
    expect(fn () => $plugin::config())->toThrow(LogicException::class, $plugin::class);
});

it('retains an absent runtime model without calling the factory again', function () {
    $plugin = new class(app()) extends Plugin
    {
        public static int $settingsCreated = 0;

        protected static function createSettings(): ?PluginSettings
        {
            self::$settingsCreated++;

            return null;
        }
    };
    $created = $plugin::create(['handle' => 'no-settings', 'settings' => ['foo' => 'Ignored']]);

    expect($created->getSettings())->toBeNull()
        ->and($created->getSettings())->toBeNull()
        ->and($plugin::$settingsCreated)->toBe(1);
});

it('includes defaults and explicit nulls without exporting builder state', function () {
    $settings = new SnapshotPluginSettings;

    expect($settings->validationData())->toBe([
        'enabled' => false, 'title' => 'Default', 'nested' => ['default' => true], 'callback' => null,
    ])
        ->and($settings->configData())->toBe($settings->validationData())
        ->and($settings->title(null))->toBe($settings)
        ->and($settings->validationData()['title'])->toBeNull()
        ->and($settings->configData())->toBe([
            'enabled' => false, 'title' => null, 'nested' => ['default' => true], 'callback' => null,
        ]);
});

it('delegates configuration data to customized validation data by default', function () {
    $settings = new class extends TestPluginSettings
    {
        public function validationData(): array
        {
            return ['custom' => 'snapshot'];
        }
    };

    expect($settings->configData())->toBe(['custom' => 'snapshot']);
});
