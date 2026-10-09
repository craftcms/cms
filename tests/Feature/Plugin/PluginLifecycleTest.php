<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    LifecycleTestPlugin::$registered = [];
    LifecycleTestPlugin::$booted = [];
    LifecycleTestPlugin::$registeredWhenBooted = [];
    LifecycleTestPlugin::$bootedWith = [];

    app()->forgetInstance(Plugins::class);
    app()->forgetInstance(FirstLifecycleTestPlugin::class);
    app()->forgetInstance(SecondLifecycleTestPlugin::class);
    app()->forgetInstance(DisabledLifecycleTestPlugin::class);
});

it('registers all enabled plugins before Laravel boots any of them', function () {
    $plugins = app(Plugins::class);

    configureLifecyclePlugins($plugins, [
        'a-first' => [FirstLifecycleTestPlugin::class, true],
        'b-second' => [SecondLifecycleTestPlugin::class, true],
        'c-disabled' => [DisabledLifecycleTestPlugin::class, false],
    ]);

    $booted = new ReflectionProperty(Application::class, 'booted');
    $wasBooted = $booted->getValue(app());
    $booted->setValue(app(), false);

    try {
        $plugins->loadPlugins();

        expect(LifecycleTestPlugin::$registered)
            ->toEqualCanonicalizing(['a-first', 'b-second'])
            ->and(LifecycleTestPlugin::$booted)->toBeEmpty();

        $bootProvider = new ReflectionMethod(Application::class, 'bootProvider');
        $bootProvider->invoke(app(), app()->getProvider(FirstLifecycleTestPlugin::class));
        $bootProvider->invoke(app(), app()->getProvider(SecondLifecycleTestPlugin::class));
    } finally {
        $booted->setValue(app(), $wasBooted);
    }

    expect(LifecycleTestPlugin::$booted)
        ->toEqualCanonicalizing(['a-first', 'b-second'])
        ->and(LifecycleTestPlugin::$registeredWhenBooted['a-first'])
        ->toEqualCanonicalizing(['a-first', 'b-second'])
        ->and(LifecycleTestPlugin::$registeredWhenBooted['b-second'])
        ->toEqualCanonicalizing(['a-first', 'b-second'])
        ->and(LifecycleTestPlugin::$bootedWith['a-first'])->toBe($plugins)
        ->and(LifecycleTestPlugin::$bootedWith['b-second'])->toBe($plugins)
        ->and($plugins->getPlugin('a-first'))
        ->toBe(app()->getProvider(FirstLifecycleTestPlugin::class))
        ->and(app()->getProvider(DisabledLifecycleTestPlugin::class))->toBeNull();
});

it('does not change the loaded plugin collection after enablement changes', function () {
    $plugins = app(Plugins::class);

    configureLifecyclePlugins($plugins, [
        'a-first' => [FirstLifecycleTestPlugin::class, true],
        'c-disabled' => [DisabledLifecycleTestPlugin::class, false],
    ]);

    $plugins->loadPlugins();
    $first = $plugins->getPlugin('a-first');

    $plugins->disablePlugin('a-first');
    $plugins->enablePlugin('c-disabled');

    expect($plugins->getPlugin('a-first'))->toBe($first)
        ->and($plugins->getPlugin('c-disabled'))->toBeNull();
});

it('returns null for plugin classes that do not implement the plugin interface', function () {
    $plugins = app(Plugins::class);

    configureLifecyclePlugins($plugins, [
        'invalid' => [stdClass::class, true],
    ]);

    expect($plugins->createPlugin('invalid'))->toBeNull();
});

it('rejects plugin classes discovered as Laravel providers', function () {
    app()->forgetInstance(Plugins::class);

    $manifest = Mockery::mock(PackageManifest::class);
    $manifest->shouldReceive('providers')->andReturn([FirstLifecycleTestPlugin::class]);
    app()->instance(PackageManifest::class, $manifest);

    $plugins = app(Plugins::class);

    configureLifecyclePlugins($plugins, [
        'a-first' => [FirstLifecycleTestPlugin::class, true],
    ]);

    $plugins->loadPlugins();
})->throws(InvalidPluginException::class, 'must not be declared as a Laravel service provider');

function configureLifecyclePlugins(Plugins $plugins, array $definitions): void
{
    $composerPluginInfo = [];

    foreach ($definitions as $handle => [$class, $enabled]) {
        $packageName = "craftcms/{$handle}";

        $composerPluginInfo[$handle] = [
            'class' => $class,
            'handle' => $handle,
            'name' => str($handle)->headline()->value(),
            'packageName' => $packageName,
            'version' => '1.0.0',
            'basePath' => dirname(new ReflectionClass(TestPlugin::class)->getFileName()),
        ];

        DB::table(Table::PLUGINS)->insert([
            'handle' => $handle,
            'version' => '1.0.0',
            'schemaVersion' => '1.0.0',
            'installDate' => now(),
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        app(ProjectConfig::class)->set(
            path: ProjectConfig::PATH_PLUGINS.'.'.$handle,
            value: [
                'edition' => 'standard',
                'enabled' => $enabled,
                'schemaVersion' => '1.0.0',
            ],
            message: "Configure {$handle} for a plugin lifecycle test",
        );
    }

    new ReflectionProperty($plugins, 'composerPluginInfo')->setValue($plugins, $composerPluginInfo);
}

abstract class LifecycleTestPlugin extends TestPlugin
{
    public static array $registered = [];

    public static array $booted = [];

    public static array $registeredWhenBooted = [];

    public static array $bootedWith = [];

    #[Override]
    public function register(): void
    {
        self::$registered[] = $this->handle;
    }

    public function boot(Plugins $plugins): void
    {
        self::$booted[] = $this->handle;
        self::$registeredWhenBooted[$this->handle] = self::$registered;
        self::$bootedWith[$this->handle] = $plugins;
    }
}

class FirstLifecycleTestPlugin extends LifecycleTestPlugin {}

class SecondLifecycleTestPlugin extends LifecycleTestPlugin {}

class DisabledLifecycleTestPlugin extends LifecycleTestPlugin {}
