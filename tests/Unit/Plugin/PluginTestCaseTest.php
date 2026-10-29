<?php

declare(strict_types=1);

use Acme\BootstrapPlugin\Plugin;
use CraftCms\Cms\Cms;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use function Orchestra\Testbench\default_skeleton_path;

it('runs a standalone plugin suite without changing workbench files', function (bool $hasProjectConfig) {
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
                if ($directory === 'bootstrap' && $file->getFilename() === 'app.php') {
                    continue;
                }

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

        File::link($package.'/vendor', $package.'/laravel/vendor');
        File::ensureDirectoryExists($package.'/vendor/laravel');
        File::link($root.'/vendor/laravel/framework', $package.'/vendor/laravel/framework');
        File::ensureDirectoryExists($package.'/vendor/craftcms');
        $manifest = "<?php\n// Composer-generated plugin manifest.\nreturn ".var_export([
            'acme/bootstrap-plugin' => [
                'class' => Plugin::class,
                'handle' => 'bootstrap-plugin',
                'name' => 'Bootstrap Plugin',
                'basePath' => $package.'/src',
                'version' => '1.0.0',
                'schemaVersion' => '1.0.0',
            ],
        ], true).";\n";
        File::put($package.'/vendor/craftcms/plugins.php', $manifest);

        if ($hasProjectConfig) {
            File::ensureDirectoryExists($package.'/laravel/config/craft/project');
            File::put($package.'/laravel/config/craft/project/project.yaml', Yaml::dump([
                'system' => ['schemaVersion' => Cms::SCHEMA_VERSION, 'name' => 'Workbench site', 'edition' => 'solo'],
                'plugins' => ['bootstrap-plugin' => ['enabled' => true, 'schemaVersion' => '1.0.0']],
                'siteGroups' => ['827ba30c-950c-41c8-a911-146181616c28' => ['name' => 'Workbench site']],
                'sites' => ['22da857f-0ce7-41be-a3b2-a961540b3aa8' => [
                    'siteGroup' => '827ba30c-950c-41c8-a911-146181616c28',
                    'name' => 'Workbench site',
                    'handle' => 'default',
                    'language' => 'en-US',
                    'baseUrl' => 'http://workbench.test/',
                    'primary' => true,
                    'hasUrls' => true,
                    'sortOrder' => 1,
                ]],
            ], 5));
        }

        $projectConfig = File::exists($package.'/laravel/config/craft/project/project.yaml')
            ? File::get($package.'/laravel/config/craft/project/project.yaml')
            : null;

        for ($run = 0; $run < 2; $run++) {
            $process = new Process(
                [PHP_BINARY, '-d', 'variables_order=EGPCS', $package.'/vendor/pestphp/pest/bin/pest', '--compact', '--colors=never'],
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
            expect(File::exists($package.'/vendor/craftcms/plugins.php'))->toBeTrue()
                ->and(File::get($package.'/vendor/craftcms/plugins.php'))->toBe($manifest);
            expect(File::exists($package.'/laravel/config/craft/project/project.yaml')
                ? File::get($package.'/laravel/config/craft/project/project.yaml')
                : null)->toBe($projectConfig);
        }
    } finally {
        File::deleteDirectory($package);
    }
})->with(['without project config' => false, 'with project config' => true]);
