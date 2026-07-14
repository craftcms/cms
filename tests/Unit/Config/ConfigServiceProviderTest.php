<?php

declare(strict_types=1);

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Env;
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
