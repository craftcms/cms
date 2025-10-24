<?php

declare(strict_types=1);

use CraftCms\Cms\Plugin\Events\LoadingPlugins;
use CraftCms\Cms\Plugin\Events\PluginSettingsSaved;
use CraftCms\Cms\Plugin\Events\PluginsLoaded;
use CraftCms\Cms\Plugin\Events\SavingPluginSettings;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\TestPlugin;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Forget the instance as the service provider loads plugins
    app()->forgetInstance(Plugins::class);

    loadTestPlugin();

    $this->plugins = app(Plugins::class);
});

afterEach(function () {
    app()->forgetInstance(Plugins::class);
});

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

it('dispatches a loading event', function () {
    app()->forgetInstance(Plugins::class);
    $this->plugins = app(Plugins::class);

    $triggeredLoading = false;
    $triggeredLoaded = false;

    Event::listen(LoadingPlugins::class, function () use (&$triggeredLoading) {
        $triggeredLoading = true;
    });

    Event::listen(PluginsLoaded::class, function () use (&$triggeredLoaded) {
        $triggeredLoaded = true;
    });

    $this->plugins->loadPlugins();

    expect($triggeredLoading)->toBeTrue('LoadingPlugins not triggered');
    expect($triggeredLoaded)->toBeTrue('PluginsLoaded not triggered');
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
    expect($this->plugins->getAllPlugins())->toHaveCount(1);
});

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

    TestPlugin::$beforeSaveSettings = true;
});

it('can run a hook on afterSaveSettings', function () {
    $triggered = false;

    TestPlugin::$onAfterSaveSettings = function () use (&$triggered) {
        $triggered = true;
    };

    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeTrue();
    expect($triggered)->toBeTrue();

    TestPlugin::$onAfterSaveSettings = null;
});

it('cannot save settings when the plugin doesnt use them', function () {
    TestPlugin::$useSettings = false;

    $plugin = $this->plugins->getPlugin('test-plugin');

    expect($plugin->getSettings())->toBeNull();

    expect($this->plugins->savePluginSettings($plugin, ['foo' => 'bar']))->toBeFalse();

    TestPlugin::$useSettings = true;
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

it('can determine if a plugin has issues', function () {
    expect($this->plugins->hasIssues('test-plugin'))->toBeFalse();

    // If CRAFT_NO_TRIALS is set, a trial value for licenseKeyStatus will be considered as an issue
    define('CRAFT_NO_TRIALS', true);

    expect($this->plugins->hasIssues('test-plugin'))->toBeTrue();
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
    expect($this->plugins->getPluginIconSvg('test-plugin'))->not()->toBeEmpty();
});
