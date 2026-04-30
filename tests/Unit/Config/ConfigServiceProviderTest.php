<?php

declare(strict_types=1);

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Support\Env;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;

afterEach(function () {
    unset(Illuminate\Support\Env::get('CRAFT_CACHED_ENV_TEST'), $_SERVER['CRAFT_CACHED_ENV_TEST']);
    putenv('CRAFT_CACHED_ENV_TEST');
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
