<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Events\PluginSettingsSaved;
use CraftCms\Cms\Plugin\Events\PluginsLoading;
use CraftCms\Cms\Plugin\Events\PluginsRegistered;
use CraftCms\Cms\Plugin\Events\SavingPluginSettings;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    TestPlugin::$useSettings = true;
    TestPlugin::$beforeSaveSettings = true;
    TestPlugin::$onAfterSaveSettings = null;
    TestPlugin::$customPublishables = [];
    TestPlugin::$customStyles = [];
    TestPlugin::$customScripts = [];

    app()->forgetInstance(Plugins::class);

    loadTestPlugin();

    $this->plugins = app(Plugins::class);

    TemplateMode::set(TemplateMode::Cp);
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/test-plugin-assets'));
    File::deleteDirectory(public_path('vendor/craftcms/test-plugin'));
    TestPlugin::$customPublishables = [];
    TestPlugin::$customStyles = [];
    TestPlugin::$customScripts = [];
    TestPlugin::$useSettings = true;
    TestPlugin::$beforeSaveSettings = true;
    TestPlugin::$onAfterSaveSettings = null;

    app()->forgetInstance(Plugins::class);
});

function configureTestPluginAssets(): array
{
    $sourcePath = storage_path('framework/testing/test-plugin-assets');
    $targetPath = public_path('vendor/craftcms/test-plugin');

    File::deleteDirectory($sourcePath);
    File::deleteDirectory($targetPath);
    File::ensureDirectoryExists("{$sourcePath}/css");
    File::ensureDirectoryExists("{$sourcePath}/js");
    File::ensureDirectoryExists("{$sourcePath}/docs");

    File::put("{$sourcePath}/css/test.css", 'body { color: red; }');
    File::put("{$sourcePath}/js/test.js", 'window.testPlugin = true;');
    File::put("{$sourcePath}/docs/readme.txt", 'Test plugin readme');

    TestPlugin::$customPublishables = [
        "{$sourcePath}/docs/readme.txt" => 'docs/readme.txt',
    ];

    TestPlugin::$customStyles = [
        "{$sourcePath}/css/test.css" => 'css/test.css',
    ];

    TestPlugin::$customScripts = [
        "{$sourcePath}/js/test.js" => 'js/test.js',
    ];

    return [
        "{$targetPath}/css/test.css",
        "{$targetPath}/js/test.js",
        "{$targetPath}/docs/readme.txt",
    ];
}

it('can load plugins', function () {
    app()->forgetInstance(Plugins::class);
    $this->plugins = app(Plugins::class);

    expect($this->plugins->arePluginsLoaded())->toBeFalse();

    $this->plugins->loadPlugins();

    expect($this->plugins->arePluginsLoaded())->toBeTrue();
});

test('plugins are singletons', function () {
    expect(TestPlugin::getInstance())
        ->toBe(TestPlugin::getInstance());
});

it('dispatches plugin lifecycle events', function () {
    app()->forgetInstance(Plugins::class);
    $this->plugins = app(Plugins::class);

    $events = [];

    Event::listen(PluginsLoading::class, function (PluginsLoading $event) use (&$events) {
        $events[] = PluginsLoading::class;
    });
    Event::listen(PluginsRegistered::class, function (PluginsRegistered $event) use (&$events) {
        $events[] = PluginsRegistered::class;
    });

    $this->plugins->loadPlugins();

    expect($events)->toBe([
        PluginsLoading::class,
        PluginsRegistered::class,
    ]);
});

it('can get a plugin by handle', function () {
    expect($this->plugins->getPlugin('test-plugin'))->toBeInstanceOf(TestPlugin::class);
});

it('can get a plugin by package name', function () {
    expect($this->plugins->getPluginByPackageName('craftcms/test-plugin'))->toBeInstanceOf(TestPlugin::class);
});

it('can get plugin handle by class', function () {
    expect($this->plugins->getPluginHandleByClass(TestPlugin::class))->toBe('test-plugin');
});

it('can get all plugins', function () {
    expect($this->plugins->getAllPlugins())->toHaveKey('test-plugin');
});

it('normalizes forced-disabled plugin configuration', function (string|array|null $disabledPlugins, bool $isForceDisabled) {
    Cms::config()->disabledPlugins = $disabledPlugins;
    app()->forgetInstance(Plugins::class);
    loadTestPlugin();

    $plugins = app(Plugins::class);

    expect($plugins->getPluginInfo('test-plugin')['isForceDisabled'])->toBe($isForceDisabled);
})->with([
    'matching string' => ['test-plugin', true],
    'non-matching string' => ['test', false],
    'matching comma-separated string' => ['other,test-plugin', true],
    'non-matching comma-separated string' => ['other,another', false],
    'matching list' => [['test-plugin'], true],
    'non-matching list' => [['test'], false],
    'empty string' => ['', false],
    'empty list' => [[], false],
    'null' => [null, false],
    'wildcard' => ['*', true],
]);

it('can enable and disable a plugin', function () {
    expect($this->plugins->isPluginEnabled('test-plugin'))->toBeFalse();
    expect($this->plugins->isPluginDisabled('test-plugin'))->toBeTrue();

    $this->plugins->enablePlugin('test-plugin');

    expect($this->plugins->isPluginEnabled('test-plugin'))->toBeTrue();
    expect($this->plugins->isPluginDisabled('test-plugin'))->toBeFalse();

    $this->plugins->disablePlugin('test-plugin');

    expect($this->plugins->isPluginEnabled('test-plugin'))->toBeFalse();
    expect($this->plugins->isPluginDisabled('test-plugin'))->toBeTrue();
});

it('can uninstall and install a plugin', function () {
    expect($this->plugins->isPluginInstalled('test-plugin'))->toBeTrue();

    $this->plugins->enablePlugin('test-plugin');
    $this->plugins->uninstallPlugin('test-plugin');

    expect($this->plugins->isPluginInstalled('test-plugin'))->toBeFalse();

    $this->plugins->installPlugin('test-plugin');

    expect($this->plugins->isPluginInstalled('test-plugin'))->toBeTrue();
    expect($this->plugins->isPluginEnabled('test-plugin'))->toBeTrue();
});

it('publishes configured files when enabling a plugin', function () {
    $paths = configureTestPluginAssets();

    $this->plugins->enablePlugin('test-plugin');

    foreach ($paths as $path) {
        expect(File::exists($path))->toBeTrue();
    }
});

it('publishes configured files when installing a plugin', function () {
    $this->plugins->enablePlugin('test-plugin');
    $this->plugins->uninstallPlugin('test-plugin');

    $paths = configureTestPluginAssets();

    $this->plugins->installPlugin('test-plugin');

    foreach ($paths as $path) {
        expect(File::exists($path))->toBeTrue();
    }
});

it('cleanly republishes configured files for enabled plugins', function () {
    $paths = configureTestPluginAssets();

    $this->plugins->enablePlugin('test-plugin');

    $stalePath = public_path('vendor/craftcms/test-plugin/stale.js');
    File::put($stalePath, 'stale');

    $this->plugins->publishPluginAssets();

    expect(File::exists($stalePath))->toBeFalse();

    foreach ($paths as $path) {
        expect(File::exists($path))->toBeTrue();
    }
});

it('ignores missing transaction exceptions during uninstall commits', function () {
    $this->plugins->enablePlugin('test-plugin');

    $manager = DB::getFacadeRoot();
    $connectionName = DB::getDefaultConnection();
    $connection = DB::connection();
    $connectionMock = Mockery::mock($connection)->makePartial();

    $connections = new ReflectionProperty($manager, 'connections');
    $resolvedConnections = $connections->getValue($manager);
    $resolvedConnections[$connectionName] = $connectionMock;
    $connections->setValue($manager, $resolvedConnections);

    $connectionMock
        ->shouldReceive('commit')
        ->once()
        ->andThrow(new PDOException('There is no active transaction'));

    try {
        $this->plugins->uninstallPlugin('test-plugin');
    } finally {
        $resolvedConnections[$connectionName] = $connection;
        $connections->setValue($manager, $resolvedConnections);
    }

    expect($this->plugins->isPluginInstalled('test-plugin'))->toBeFalse();
});

it('cannot uninstall a plugin that is not enabled', function () {
    expect($this->plugins->isPluginEnabled('test-plugin'))->toBeFalse();

    $this->plugins->uninstallPlugin('test-plugin');
})->throws(InvalidPluginException::class, 'Uninstalling disabled plugins is not allowed.');

it('cannot switch to an edition that doesnt exist', function () {
    expect($this->plugins->getPlugin('test-plugin')->edition)->toBe('standard');

    $this->plugins->switchEdition('test-plugin', 'notavalidedition');
})->throws(InvalidArgumentException::class, 'Invalid plugin edition: notavalidedition');

it('can switch editions', function () {
    expect($this->plugins->getPlugin('test-plugin')->edition)->toBe('standard');

    $this->plugins->switchEdition('test-plugin', 'pro');

    expect($this->plugins->getPlugin('test-plugin')->edition)->toBe('pro');
});

it('can save settings', function () {
    Event::fake();

    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($plugin->getSettings()->foo)->toBeNull();

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeTrue();

    expect($this->plugins->getPlugin('test-plugin')->getSettings()->foo)->toEqual('bar');

    Event::assertDispatched(SavingPluginSettings::class);
    Event::assertDispatched(PluginSettingsSaved::class);
});

it('prefers plugin config values over stored settings', function () {
    app()->offsetUnset(TestPlugin::class);

    Config::set('craft.test-plugin', [
        'foo' => 'from-config',
    ]);

    $plugin = $this->plugins->createPlugin('test-plugin', [
        ...$this->plugins->getStoredPluginInfo('test-plugin'),
        'settings' => [
            'foo' => 'from-settings',
        ],
    ]);

    expect($plugin)->toBeInstanceOf(TestPlugin::class);
    expect($plugin->getSettings()?->foo)->toBe('from-config');
});

it('can cancel saving with a before event', function () {
    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($plugin->getSettings()->foo)->toBeNull();

    Event::listen(SavingPluginSettings::class, function (SavingPluginSettings $event) {
        $event->isValid = false;
    });

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeFalse();
});

it('can cancel saving with beforeSaveSettings', function () {
    TestPlugin::$beforeSaveSettings = false;

    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($plugin->getSettings()->foo)->toBeNull();

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeFalse();
});

it('can run a hook on afterSaveSettings', function () {
    $triggered = false;

    TestPlugin::$onAfterSaveSettings = function () use (&$triggered) {
        $triggered = true;
    };

    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeTrue();
    expect($triggered)->toBeTrue();
});

it('cannot save settings when the plugin doesnt use them', function () {
    TestPlugin::$useSettings = false;

    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($plugin->getSettings())->toBeNull();

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeFalse();
});

it('can determine if the version number changed', function () {
    /** @var TestPlugin $plugin */
    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($this->plugins->hasPluginVersionNumberChanged($plugin))->toBeFalse();

    $plugin->version = '1.0.2';

    expect($this->plugins->hasPluginVersionNumberChanged($plugin))->toBeTrue();
});

it('can determine if a plugin update is pending', function () {
    /** @var TestPlugin $plugin */
    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($this->plugins->isPluginUpdatePending($plugin))->toBeFalse();

    $plugin->schemaVersion = '1.0.2';

    expect($this->plugins->isPluginUpdatePending($plugin))->toBeTrue();
});

it('can update version info', function () {
    /** @var TestPlugin $plugin */
    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($this->plugins->getStoredPluginInfo('test-plugin')['version'])->toBe($plugin->version);
    expect($this->plugins->getStoredPluginInfo('test-plugin')['schemaVersion'])->toBe($plugin->schemaVersion);

    $plugin->version = '1.0.2';
    $plugin->schemaVersion = '1.0.2';

    $this->plugins->updatePluginVersionInfo($plugin);

    expect($this->plugins->getStoredPluginInfo('test-plugin')['version'])->toBe('1.0.2');
    expect($this->plugins->getStoredPluginInfo('test-plugin')['schemaVersion'])->toBe('1.0.2');
});

it('can get composer plugin info', function () {
    expect($this->plugins->getComposerPluginInfo('test-plugin'))->not()->toBeNull();
});

it('can get all plugin info', function () {
    expect($this->plugins->getAllPluginInfo())->toHaveKey('test-plugin');
});

it('does not report issues for a valid plugin', function () {
    expect($this->plugins->hasIssues('test-plugin'))->toBeFalse();
});

it('can get and set the license key', function () {
    expect($this->plugins->getPluginLicenseKey('test-plugin'))->toBeNull();

    $this->plugins->setPluginLicenseKey('test-plugin', $key = Str::random(24));

    expect($this->plugins->getPluginLicenseKey('test-plugin'))
        ->toBe($this->plugins->normalizePluginLicenseKey($key));
});

it('can get the plugin license key status', function () {
    expect($this->plugins->getPluginLicenseKeyStatus('test-plugin'))->toBe(LicenseKeyStatus::Trial);
});

it('can get the plugin icon', function () {
    expect($this->plugins->getPluginIconSvg('test-plugin'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/TestClasses/TestPlugin/src/icon.svg'));
});
