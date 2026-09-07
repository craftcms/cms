<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Config\BaseConfig;
use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Events\PluginInstalled;
use CraftCms\Cms\Plugin\Events\PluginInstalling;
use CraftCms\Cms\Plugin\Events\PluginSettingsSaved;
use CraftCms\Cms\Plugin\Events\PluginsLoading;
use CraftCms\Cms\Plugin\Events\PluginsRegistered;
use CraftCms\Cms\Plugin\Events\PluginUninstalled;
use CraftCms\Cms\Plugin\Events\PluginUninstalling;
use CraftCms\Cms\Plugin\Events\SavingPluginSettings;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\FluentTestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\SnapshotPluginSettings;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPluginSettings;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Fluent;

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

it('restores project config permissions after plugin lifecycle failures', function (string $operation, string $event, bool $installed, bool $readOnly) {
    $this->plugins->enablePlugin('test-plugin');

    if ($operation === 'installPlugin') {
        $this->plugins->uninstallPlugin('test-plugin');
    }

    $failure = new RuntimeException('Plugin lifecycle failed.');
    Event::listen($event, fn () => throw $failure);

    $projectConfig = app(ProjectConfig::class);
    $originalReadOnly = $projectConfig->readOnly;
    $projectConfig->readOnly = $readOnly;

    try {
        expect(fn () => $this->plugins->{$operation}('test-plugin'))->toThrow($failure);

        expect($projectConfig->readOnly)->toBe($readOnly)
            ->and($this->plugins->isPluginInstalled('test-plugin'))->toBe($installed);
    } finally {
        $projectConfig->readOnly = $originalReadOnly;
    }
})->with([
    'before installation' => ['installPlugin', PluginInstalling::class, false],
    'after installation' => ['installPlugin', PluginInstalled::class, true],
    'before uninstallation' => ['uninstallPlugin', PluginUninstalling::class, true],
    'after uninstallation' => ['uninstallPlugin', PluginUninstalled::class, false],
])->with([true, false]);

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

it('applies array and object configuration over stored settings without changing the input object', function (string $kind, ?string $expectedBar) {
    app()->offsetUnset(TestPlugin::class);
    $craftConfig = Config::get('craft', []);
    unset($craftConfig['test-plugin']);
    Config::set('craft', $craftConfig);
    $input = match ($kind) {
        'missing' => null,
        'array' => ['foo' => 'File'],
        'object' => TestPluginSettings::create()->foo('File'),
        'explicit null' => TestPluginSettings::create()->foo(null)->bar(null),
    };
    if ($kind !== 'missing') {
        Config::set('craft.test-plugin', $input);
    }
    new ConfigServiceProvider(app())->register();

    $plugin = $this->plugins->createPlugin('test-plugin', ['settings' => ['foo' => 'Stored', 'bar' => 'Stored bar']]);
    $settings = $plugin->getSettings();

    expect($settings)->toBeInstanceOf(TestPluginSettings::class)->toBe($plugin->getSettings())->not->toBe($input)
        ->and($settings->foo)->toBe(match ($kind) {
            'missing' => 'Stored',
            'explicit null' => null,
            default => 'File',
        })
        ->and($settings->bar)->toBe($expectedBar);

    $settings->foo = 'Runtime';
    if ($input instanceof PluginSettings) {
        expect(Config::get('craft.test-plugin'))->toBe($input->validationData())
            ->and($input->foo)->toBe($kind === 'explicit null' ? null : 'File');
    }
})->with([
    'missing' => ['missing', 'Stored bar'],
    'sparse array' => ['array', 'Stored bar'],
    'full object' => ['object', null],
    'explicit null' => ['explicit null', null],
]);

it('hydrates plugin-static configuration before register and boot while keeping static calls fresh', function () {
    $composerInfo = $this->plugins->getComposerPluginInfo('test-plugin');
    $composerInfo['class'] = FluentTestPlugin::class;
    new ReflectionProperty(Plugins::class, 'composerPluginInfo')->setValue($this->plugins, ['test-plugin' => $composerInfo]);
    $input = FluentTestPlugin::settings()->foo('File');
    Config::set('craft.test-plugin', $input);
    new ConfigServiceProvider(app())->register();

    $plugin = $this->plugins->createPlugin('test-plugin', ['settings' => ['foo' => 'Stored', 'bar' => 'Stored bar']]);
    $runtime = $plugin->getSettings();
    app()->register($plugin);

    expect($plugin)->toBeInstanceOf(FluentTestPlugin::class)
        ->and($plugin->registeredSettings)->toBe(['foo' => 'File', 'bar' => null])
        ->and($plugin->bootedSettings)->toBe(['foo' => 'File', 'bar' => null])
        ->and(FluentTestPlugin::getInstance()->getSettings())->toBe($runtime)
        ->and($runtime)->not->toBe($input);

    $runtime->foo = 'Runtime';
    $fresh = FluentTestPlugin::settings()->foo('Fresh');
    $another = FluentTestPlugin::settings();

    expect($fresh)->not->toBe($input)->not->toBe($runtime)->not->toBe($another)
        ->and($another->configData())->toBe(['foo' => null, 'bar' => null])
        ->and($plugin->getSettings())->toBe($runtime)
        ->and($runtime->foo)->toBe('Runtime')
        ->and(Config::get('craft.test-plugin'))->toBe(['foo' => 'File', 'bar' => null])
        ->and($input->foo)->toBe('File');
});

it('uses full defaults and shallow nested replacement without replaying fluent setters', function (bool $object) {
    app()->offsetUnset(TestPlugin::class);
    app()->bind(TestPlugin::class, fn () => new class(app()) extends TestPlugin
    {
        protected function createSettingsModel(): SnapshotPluginSettings
        {
            return new SnapshotPluginSettings;
        }
    });
    $input = SnapshotPluginSettings::create();
    $input->title = '  File  ';
    $input->enabled = false;
    $input->nested = ['file' => true];
    Config::set('craft.test-plugin', $object ? $input : ['title' => '  File  ', 'nested' => ['file' => true], 'unknown' => 'ignored']);
    new ConfigServiceProvider(app())->register();

    $plugin = $this->plugins->createPlugin('test-plugin', ['settings' => [
        'title' => 'Stored', 'enabled' => true, 'nested' => ['stored' => true],
    ]]);

    expect($plugin->getSettings()->title)->toBe('  File  ')
        ->and($plugin->getSettings()->enabled)->toBe(! $object)
        ->and($plugin->getSettings()->nested)->toBe(['file' => true]);
})->with(['array' => false, 'object' => true]);

it('hydrates the factory model with typecasting rather than adopting the input class', function () {
    app()->offsetUnset(TestPlugin::class);
    $input = new class extends PluginSettings
    {
        public int $foo = 42;

        public string $bar = '';

        public string $unknown = 'ignored';
    };
    Config::set('craft.test-plugin', $input);
    new ConfigServiceProvider(app())->register();

    $settings = $this->plugins->createPlugin('test-plugin', [])->getSettings();

    expect($settings)->toBeInstanceOf(TestPluginSettings::class)
        ->and($settings->validationData())->toBe(['foo' => '42', 'bar' => null])
        ->and(Config::get('craft.test-plugin'))->toBe(['foo' => 42, 'bar' => '', 'unknown' => 'ignored']);
});

it('retains later pluginConfigs precedence for object input', function () {
    app()->offsetUnset(TestPlugin::class);
    $input = TestPluginSettings::create()->foo('File');
    Config::set('craft.test-plugin', $input);
    new ConfigServiceProvider(app())->register();
    $this->plugins->pluginConfigs = ['test-plugin' => ['settings' => ['foo' => 'Custom']]];

    $plugin = $this->plugins->createPlugin('test-plugin', ['settings' => ['foo' => 'Stored']]);

    expect($plugin->getSettings()->foo)->toBe('Custom')
        ->and($input->foo)->toBe('File');
});

it('rejects unsupported configuration with its key and actual type', function (Closure $makeInput) {
    $input = $makeInput();
    Config::set('craft.test-plugin', $input);

    expect(fn () => $this->plugins->createPlugin('test-plugin', []))
        ->toThrow(InvalidArgumentException::class, 'Configuration [craft.test-plugin] must be an array; got '.get_debug_type($input).'.');
})->with([
    'null' => [fn () => null],
    'string' => [fn () => 'invalid'],
    'integer' => [fn () => 42],
    'float' => [fn () => 1.5],
    'boolean' => [fn () => false],
    'closure' => [fn () => fn () => []],
    'base config' => [fn () => new class extends BaseConfig {}],
    'arrayable' => [fn () => new Fluent(['foo' => 'File'])],
    'validatable' => [fn () => new class extends Component {}],
    'late settings object' => [TestPluginSettings::create(...)],
]);

it('keeps normalized snapshots compatible with absent models including empty snapshots', function (bool $empty) {
    app()->offsetUnset(TestPlugin::class);
    TestPlugin::$useSettings = false;
    Config::set('craft.test-plugin', $empty ? new class extends PluginSettings {} : TestPluginSettings::create()->foo('File'));
    new ConfigServiceProvider(app())->register();

    expect($this->plugins->createPlugin('test-plugin', [])->getSettings())->toBeNull();
})->with([true, false]);

it('keeps array input compatible with plugins without settings', function () {
    app()->offsetUnset(TestPlugin::class);
    TestPlugin::$useSettings = false;
    Config::set('craft.test-plugin', ['foo' => 'File']);

    expect($this->plugins->createPlugin('test-plugin', [])->getSettings())->toBeNull();
});

it('accepts an empty snapshot when the plugin has a model', function () {
    app()->offsetUnset(TestPlugin::class);
    Config::set('craft.test-plugin', new class extends PluginSettings {});
    new ConfigServiceProvider(app())->register();

    expect($this->plugins->createPlugin('test-plugin', [])->getSettings())->toBeInstanceOf(TestPluginSettings::class);
});

it('does not read file overrides without installed information', function () {
    app()->offsetUnset(TestPlugin::class);
    Config::set('craft.test-plugin', fn () => throw new RuntimeException('Not evaluated'));

    expect($this->plugins->createPlugin('test-plugin')->getSettings()->foo)->toBeNull();
});

it('saves submitted overrides and omitted file values without mutating configuration input', function () {
    app()->offsetUnset(TestPlugin::class);
    $input = TestPluginSettings::create()->foo('File foo')->bar('File bar');
    Config::set('craft.test-plugin', $input);
    new ConfigServiceProvider(app())->register();
    $plugin = $this->plugins->createPlugin('test-plugin', []);
    $settings = $plugin->getSettings();

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'Submitted']))->toBeTrue();

    expect($plugin->getSettings())->toBe($settings)
        ->and($settings->validationData())->toBe(['foo' => 'Submitted', 'bar' => 'File bar'])
        ->and(app(ProjectConfig::class)->get('plugins.test-plugin.settings'))->toBe(['bar' => 'File bar', 'foo' => 'Submitted'])
        ->and(Config::get('craft.test-plugin'))->toBe(['foo' => 'File foo', 'bar' => 'File bar'])
        ->and($input->validationData())->toBe(['foo' => 'File foo', 'bar' => 'File bar']);
});

it('saves full validation data even when configuration data is customized', function () {
    app()->offsetUnset(TestPlugin::class);
    $input = new class extends TestPluginSettings
    {
        public function configData(): array
        {
            return ['foo' => 'Config foo'];
        }
    };
    $input->foo('Input foo')->bar('Input bar');
    app()->bind(TestPlugin::class, fn () => new class(app(), $input) extends TestPlugin
    {
        public function __construct(Application $app, private readonly TestPluginSettings $settingsInput)
        {
            parent::__construct($app);
        }

        protected function createSettingsModel(): TestPluginSettings
        {
            return clone $this->settingsInput;
        }
    });
    Config::set('craft.test-plugin', $input);
    new ConfigServiceProvider(app())->register();
    $plugin = $this->plugins->createPlugin('test-plugin', []);

    expect($plugin->getSettings()->validationData())->toBe(['foo' => 'Config foo', 'bar' => 'Input bar']);
    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'Submitted', 'bar' => 'Saved bar']))->toBeTrue();

    expect($plugin->getSettings()->validationData())->toBe(['foo' => 'Submitted', 'bar' => 'Saved bar'])
        ->and($plugin->getSettings()->configData())->toBe(['foo' => 'Config foo'])
        ->and(app(ProjectConfig::class)->get('plugins.test-plugin.settings'))->toBe(['bar' => 'Saved bar', 'foo' => 'Submitted'])
        ->and(Config::get('craft.test-plugin'))->toBe(['foo' => 'Config foo'])
        ->and($input->validationData())->toBe(['foo' => 'Input foo', 'bar' => 'Input bar']);
});

it('retains live mutations but does not stage failed or canceled settings saves', function (string $failure) {
    app()->offsetUnset(TestPlugin::class);
    $input = TestPluginSettings::create()->foo('File')->bar('File bar');
    Config::set('craft.test-plugin', $input);
    new ConfigServiceProvider(app())->register();
    $plugin = $this->plugins->createPlugin('test-plugin', []);
    $previous = app(ProjectConfig::class)->get('plugins.test-plugin.settings');
    if ($failure === 'hook') {
        TestPlugin::$beforeSaveSettings = false;
    }
    if ($failure === 'event') {
        Event::listen(SavingPluginSettings::class, function (SavingPluginSettings $event) {
            $event->isValid = false;
        });
    }
    $submitted = $failure === 'validation' ? null : 'Submitted';

    expect($this->plugins->savePluginSettings($plugin, ['foo' => $submitted]))->toBeFalse();

    expect($plugin->getSettings()->foo)->toBe($submitted)
        ->and(app(ProjectConfig::class)->get('plugins.test-plugin.settings'))->toBe($previous)
        ->and(Config::get('craft.test-plugin'))->toBe(['foo' => 'File', 'bar' => 'File bar'])
        ->and($input->validationData())->toBe(['foo' => 'File', 'bar' => 'File bar']);
})->with(['validation', 'hook', 'event']);

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

it('reads refreshed plugin license metadata after production hydration', function (bool $canTest, bool $enabled) {
    $this->plugins->enablePlugin('test-plugin');
    $this->plugins->uninstallPlugin('test-plugin');
    $this->plugins->installPlugin('test-plugin');
    if (! $enabled) {
        $this->plugins->disablePlugin('test-plugin');
    }
    new ReflectionProperty(Plugins::class, 'pluginsLoaded')->setValue($this->plugins, false);
    $this->plugins->loadPlugins();
    Cache::put('editionTestableDomain@'.request()->host(), $canTest);

    foreach ([['valid', 'standard', []], ['invalid', null, ['invalid']], ['trial', 'standard', ['required']], ['mismatched', 'pro', ['wrong_edition', 'mismatched']], ['astray', 'standard', ['astray']], ['valid', 'pro', ['wrong_edition']], ['unknown', null, []]] as [$status, $edition, $issues]) {
        Cache::put(License::CACHE_KEY_LICENSE_INFO, [
            'plugin-test-plugin' => ['id' => 123, 'edition' => $edition, 'status' => $status],
        ]);
        $issues = $canTest ? array_values(array_diff($issues, ['wrong_edition', 'required'])) : $issues;
        $info = $this->plugins->getPluginInfo('test-plugin');

        expect($info)->toMatchArray([
            'licenseId' => 123, 'licensedEdition' => $edition, 'licenseKeyStatus' => $status,
            'licenseIssues' => $issues, 'isTrial' => $status === 'trial' || ($status === 'valid' && $edition === 'pro'),
            'isEnabled' => $enabled,
        ]);
        expect($this->plugins->getPluginLicenseKeyStatus('test-plugin'))->toBe(LicenseKeyStatus::from($status));
        expect($this->plugins->getLicenseIssues('test-plugin'))->toBe($issues);
        expect($this->plugins->hasIssues('test-plugin'))->toBe($issues !== []);
    }
})->with([true, false])->with([true, false]);

it('can get the plugin icon', function () {
    expect($this->plugins->getPluginIconSvg('test-plugin'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/TestClasses/TestPlugin/src/icon.svg'));
});
