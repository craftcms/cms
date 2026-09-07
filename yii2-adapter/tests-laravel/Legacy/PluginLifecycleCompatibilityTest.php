<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields as LegacyFields;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugin as ModernPlugin;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPluginSettings;
use CraftCms\Yii2Adapter\Event\EventCompatibility;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;

beforeEach(function() {
    $this->app->register(Yii2ServiceProvider::class);
});

afterEach(function() {
    YiiEvent::off(LegacyFields::class, LegacyFields::EVENT_REGISTER_FIELD_TYPES);
});

it('creates and runs adapter plugins through the shared plugin interface', function() {
    $plugins = app(Plugins::class);

    new ReflectionProperty($plugins, 'composerPluginInfo')->setValue($plugins, [
        'legacy' => [
            'class' => AdapterLifecycleTestPlugin::class,
            'handle' => 'legacy',
            'name' => 'Legacy',
            'packageName' => 'craftcms/legacy',
            'version' => '1.0.0',
            'basePath' => __DIR__,
        ],
    ]);

    $plugin = $plugins->createPlugin('legacy');

    expect($plugin)
        ->toBeInstanceOf(PluginInterface::class)
        ->toBeInstanceOf(AdapterLifecycleTestPlugin::class)
        ->and($plugin->getSettings())->toBeNull()
        ->and($plugin->getSettings())->toBeNull();

    expect(function() use ($plugin, $plugins): void {
        $plugin->bootPlugin($plugins);
        $plugin->publishAssets();
        $plugin->removeAssets();
    })->not()->toThrow(Throwable::class);
});

it('hydrates validates and saves plugin settings through the legacy service', function(?string $submitted) {
    $plugin = AdapterSettingsTestPlugin::create([
        'handle' => 'legacy-settings',
        'name' => 'Legacy Settings',
        'basePath' => __DIR__,
        'settings' => ['foo' => 'Stored'],
    ]);
    $settings = $plugin->getSettings();
    $plugins = new craft\services\Plugins();

    expect($settings)->toBeInstanceOf(PluginSettings::class)
        ->toBe($plugin->getSettings())
        ->and($settings->foo)->toBe('Stored');

    $saved = $plugins->savePluginSettings($plugin, ['foo' => $submitted]);

    expect($saved)->toBe($submitted !== null)
        ->and($settings->errors()->has('foo'))->toBe($submitted === null)
        ->and($plugin->getSettings())->toBe($settings)
        ->and($settings->foo)->toBe($submitted)
        ->and(app(ProjectConfig::class)->get('plugins.legacy-settings.settings'))->toBe($submitted === null ? null : [
            'bar' => null,
            'foo' => 'Submitted',
        ]);
})->with(['invalid' => null, 'valid' => 'Submitted']);

it('reconciles legacy registrations after legacy and modern plugins register types', function() {
    $plugins = app(Plugins::class);
    AdapterRegistrationTestPlugin::$modernTypeWasVisible = false;

    new ReflectionProperty($plugins, 'composerPluginInfo')->setValue($plugins, [
        'legacy-registration' => [
            'class' => AdapterRegistrationTestPlugin::class,
            'handle' => 'legacy-registration',
            'name' => 'Legacy registration',
            'packageName' => 'craftcms/legacy-registration',
            'version' => '1.0.0',
            'basePath' => __DIR__,
        ],
    ]);

    $compatibility = new EventCompatibility();
    $plugins->createPlugin('legacy-registration');

    $modernPlugin = AdapterModernRegistrationTestPlugin::create([
        'handle' => 'modern-registration',
        'name' => 'Modern registration',
        'packageName' => 'craftcms/modern-registration',
        'version' => '1.0.0',
    ]);
    $modernPlugin->bootPlugin($plugins);

    $compatibility->finalizeRegistrationEvents();

    expect(app(FieldTypes::class)->types())
        ->toContain(AdapterLegacyRegistrationField::class, AdapterModernRegistrationField::class)
        ->and(AdapterRegistrationTestPlugin::$modernTypeWasVisible)->toBeTrue();
});

class AdapterLifecycleTestPlugin extends Plugin
{
}

class AdapterSettingsTestPlugin extends Plugin
{
    protected function createSettingsModel(): PluginSettings
    {
        return TestPluginSettings::create();
    }
}

class AdapterRegistrationTestPlugin extends Plugin
{
    public static bool $modernTypeWasVisible = false;

    public function init(): void
    {
        parent::init();

        YiiEvent::on(LegacyFields::class, LegacyFields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            self::$modernTypeWasVisible = in_array(AdapterModernRegistrationField::class, $event->types, true);
            $event->types[] = AdapterLegacyRegistrationField::class;
        });
    }
}

class AdapterModernRegistrationTestPlugin extends ModernPlugin
{
    protected array $fieldTypes = [AdapterModernRegistrationField::class];
}

abstract class AdapterLegacyRegistrationField extends Field
{
}

abstract class AdapterModernRegistrationField extends Field
{
}
