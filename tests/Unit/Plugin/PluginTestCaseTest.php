<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

use function Orchestra\Testbench\default_skeleton_path;

it('runs a standalone plugin suite without Craft workbench providers', function () {
    $root = dirname(__DIR__, 3);
    $package = sys_get_temp_dir().'/craft-plugin-suite-'.bin2hex(random_bytes(8));

    try {
        File::copyDirectory($root.'/tests/TestClasses/PluginTestSuite', $package);
        $skeleton = default_skeleton_path();

        foreach (['app', 'lang', 'migrations', 'resources', 'routes'] as $directory) {
            File::copyDirectory($skeleton.'/'.$directory, $package.'/laravel/'.$directory);
        }

        foreach (['bootstrap', 'config'] as $directory) {
            File::ensureDirectoryExists($package.'/laravel/'.$directory);

            foreach (File::files($skeleton.'/'.$directory) as $file) {
                File::copy($file->getPathname(), $package.'/laravel/'.$directory.'/'.$file->getFilename());
            }
        }

        File::copy($skeleton.'/composer.json', $package.'/laravel/composer.json');

        foreach (['bootstrap/cache', 'database/migrations', 'public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) {
            File::ensureDirectoryExists($package.'/laravel/'.$directory);
        }

        File::ensureDirectoryExists($package.'/vendor/pestphp/pest/bin');
        File::copy($root.'/vendor/pestphp/pest/bin/pest', $package.'/vendor/pestphp/pest/bin/pest');

        $autoload = var_export($root.'/vendor/autoload.php', true);
        File::put($package.'/vendor/autoload.php', <<<PHP
            <?php
            \$loader = require $autoload;
            \$loader->addPsr4('Acme\\\\BootstrapPlugin\\\\', dirname(__DIR__).'/src');
            \$loader->addPsr4('Acme\\\\BootstrapPlugin\\\\Tests\\\\', dirname(__DIR__).'/tests');
            return \$loader;
            PHP);

        for ($run = 0; $run < 2; $run++) {
            $process = new Process(
                [PHP_BINARY, $package.'/vendor/pestphp/pest/bin/pest', '--compact', '--colors=never'],
                $package,
                [
                    'TESTBENCH_WORKING_PATH' => $package,
                    'TESTBENCH_APP_BASE_PATH' => $package.'/laravel',
                    'APP_BASE_PATH' => false,
                    'TEST_TOKEN' => false,
                    'LARAVEL_PARALLEL_TESTING' => false,
                ],
                timeout: 60,
            );
            $process->run();

            expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
        }
    } finally {
        File::deleteDirectory($package);
    }
});
