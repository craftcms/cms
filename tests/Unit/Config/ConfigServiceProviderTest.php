<?php

declare(strict_types=1);

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\SnapshotPluginSettings;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;

afterEach(function () {
    unset($_SERVER['CRAFT_CACHED_ENV_TEST']);
    putenv('CRAFT_CACHED_ENV_TEST');

    foreach (['CRAFT_CP_TRIGGER', 'CRAFT_DEFAULT_COUNTRY_CODE'] as $name) {
        unset($_SERVER[$name]);
        putenv($name);
    }
});

it('normalizes only direct settings before boot without resolving runtime services', function () {
    $input = SnapshotPluginSettings::create()->title(' File ');
    $state = (array) $input;
    $general = GeneralConfig::create();
    $closure = fn () => throw new RuntimeException('Not invoked');
    $untouched = [
        'general' => $general,
        'sparse' => ['title' => 'File'],
        'nested' => ['object' => $input],
        'scalar' => 42,
        'null' => null,
        'closure' => $closure,
        'unrelated' => new stdClass,
    ];
    $repository = new Repository(['craft' => [
        ...$untouched,
        'unknown.plugin' => $input,
        'disabled' => SnapshotPluginSettings::create(),
        'empty' => new class extends PluginSettings {},
    ], 'other' => $input]);
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $app->instance('config', $repository);
    foreach ([Plugins::class, TestPlugin::class, 'db', 'db.connection', 'session', 'session.store', 'view', 'validator'] as $service) {
        $app->bind($service, fn () => throw new RuntimeException("Unexpected resolution: $service"));
        app()->beforeResolving($service, fn () => throw new RuntimeException("Unexpected parent resolution: $service"));
    }

    new ConfigServiceProvider($app)->register();

    $normalized = $repository->get('craft');
    expect($app->isBooted())->toBeFalse()
        ->and($normalized['unknown.plugin'])->toBe($input->validationData())
        ->and($normalized['disabled']['title'])->toBe('Default')
        ->and($normalized['empty'])->toBe([])
        ->and($repository->get('other'))->toBe($input)
        ->and((array) $input)->toBe($state);
    foreach ($untouched as $key => $value) {
        expect($normalized[$key])->toBe($value);
    }

    new ConfigServiceProvider($app)->register();

    expect($repository->get('craft'))->toBe($normalized)
        ->and((array) $input)->toBe($state);
});

it('uses custom configuration data without changing validation data', function () {
    $input = new class extends PluginSettings
    {
        private string $builderValue = 'derived';

        public string $title = 'Validation title';

        public function configData(): array
        {
            return ['title' => $this->builderValue];
        }
    };
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $repository = new Repository(['craft' => ['custom' => $input]]);
    $app->instance('config', $repository);

    new ConfigServiceProvider($app)->register();

    expect($repository->get('craft.custom'))->toBe(['title' => 'derived'])
        ->and($input->validationData())->toBe(['title' => 'Validation title']);
});

it('does not rewrite the root without settings conversions', function (bool $missing) {
    $repository = Mockery::mock(Repository::class)->makePartial();
    if (! $missing) {
        $repository->set('craft', ['sparse' => ['foo' => null]]);
    }
    $repository->shouldNotReceive('set')->with('craft', Mockery::any());
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $app->instance('config', $repository);

    new ConfigServiceProvider($app)->register();

    expect($repository->get('craft.sparse'))->toBe($missing ? null : ['foo' => null]);
})->with([true, false]);

it('rejects malformed craft roots', function (mixed $root) {
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $app->instance('config', new Repository(['craft' => $root]));

    expect(fn () => new ConfigServiceProvider($app)->register())
        ->toThrow(InvalidArgumentException::class, 'Configuration [craft] must be an array; got '.get_debug_type($root).'.');
})->with([null, false, 42, 'invalid']);

it('loads environment variables when configuration is cached', function () {
    $filesystem = new Filesystem;
    $basePath = sys_get_temp_dir().'/craft-cached-env-test-'.bin2hex(random_bytes(8));

    try {
        $filesystem->ensureDirectoryExists("$basePath/bootstrap/cache");
        $filesystem->put("$basePath/bootstrap/cache/config.php", '<?php return [];');
        $filesystem->put("$basePath/.env", 'CRAFT_CACHED_ENV_TEST=loaded-from-dotenv'.PHP_EOL);

        $app = new Application($basePath);
        $app->useEnvironmentPath($basePath);

        expect($app->configurationIsCached())->toBeTrue();
        expect(Env::parse('$CRAFT_CACHED_ENV_TEST'))->toBeNull();

        new ConfigServiceProvider($app)->register();

        expect($app->configurationIsCached())->toBeTrue();
        expect(Env::parse('$CRAFT_CACHED_ENV_TEST'))->toBe('loaded-from-dotenv');
    } finally {
        $filesystem->deleteDirectory($basePath);
    }
});

it('materializes array configuration through fluent setters', function () {
    app(ConfigRepository::class)->set('craft.general', [
        'extraAllowedFileExtensions' => ['CUSTOM'],
    ]);
    app()->forgetInstance(GeneralConfig::class);

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class);

    expect($config->allowedFileExtensions)->toContain('custom')
        ->and(app(ConfigRepository::class)->get('craft.general'))->toBe($config);
});

it('applies environment overrides when resolved', function () {
    app(ConfigRepository::class)->set('craft.general', [
        'cpTrigger' => 'control',
    ]);
    app()->forgetInstance(GeneralConfig::class);
    putenv('CRAFT_CP_TRIGGER=adminus');

    new ConfigServiceProvider(app())->register();

    expect(app(GeneralConfig::class)->cpTrigger)->toBe('adminus');
});

it('fails when an environment override cannot be normalized', function () {
    app(ConfigRepository::class)->set('craft.general', [
        'defaultCountryCode' => 'US',
    ]);
    app()->forgetInstance(GeneralConfig::class);
    putenv('CRAFT_DEFAULT_COUNTRY_CODE=');

    new ConfigServiceProvider(app())->register();

    expect(fn () => app(GeneralConfig::class))
        ->toThrow(RuntimeException::class);

    expect(app(ConfigRepository::class)->get('craft.general'))->toBe([
        'defaultCountryCode' => 'US',
    ]);
});
