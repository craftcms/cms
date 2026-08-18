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

    new ReflectionProperty(Plugins::class, 'composerPluginInfo')->setValue($plugins, [
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
        ->toBeInstanceOf(AdapterLifecycleTestPlugin::class);

    expect(function() use ($plugin, $plugins): void {
        $plugin->bootPlugin($plugins);
        $plugin->publishAssets();
        $plugin->removeAssets();
    })->not()->toThrow(Throwable::class);
});

it('reconciles legacy registrations after legacy and modern plugins register types', function() {
    $plugins = app(Plugins::class);
    AdapterRegistrationTestPlugin::$modernTypeWasVisible = false;

    new ReflectionProperty(Plugins::class, 'composerPluginInfo')->setValue($plugins, [
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
