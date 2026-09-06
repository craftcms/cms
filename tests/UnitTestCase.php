<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\IsolatesParallelFiles;
use CraftCms\Cms\Tests\Support\RegistersPackageAliases;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

/**
 * Lightweight test case for unit tests that only need the Laravel
 * service container (no database, no Yii2 bootstrap, no migrations).
 *
 * Use this for tests that don't touch the database or legacy Yii2 code.
 */
class UnitTestCase extends Orchestra
{
    use IsolatesParallelFiles;
    use RegistersPackageAliases;
    use WithWorkbench;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        unset($_SERVER['CRAFT_EDITION']);
        putenv('CRAFT_EDITION');

        Context::forgetHidden(Edition::class);
        Context::forgetHidden('craft.isInstalled');
        Context::forgetHidden('craft.info');

        tap(app(ConfigRepository::class), function (ConfigRepository $config) {
            $config->set('database.default', 'sqlite');
            $config->set('database.connections.sqlite', array_merge(
                $config->get('database.connections.sqlite', []),
                [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
            ));
        });

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Cms::setIsInstalled(false);

        Edition::set(Edition::Pro);
        TemplateMode::set(TemplateMode::Cp);

        Sites::setCurrentSite(new Site);

        app()->setLocale('en-US');

        Cms::config()->timezone('America/Los_Angeles');
        Cms::setDefaultTimezone();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        $projectConfigFolder = 'project';

        if (($token = getenv('TEST_TOKEN')) !== false) {
            $projectConfigFolder .= "_$token";
            $app->useStoragePath($app->storagePath("parallel_$token"));
            File::ensureDirectoryExists($app->storagePath('framework/testing'));

            $app->afterResolving(ProjectConfig::class, function (ProjectConfig $projectConfig) use ($projectConfigFolder) {
                $projectConfig->folderName = $projectConfigFolder;
                $projectConfig->writeYamlAutomatically = false;
            });
        }

        File::cleanDirectory($app->configPath("craft/$projectConfigFolder"));
        File::cleanDirectory($app->storagePath('runtime/compiled_classes'));
        File::cleanDirectory($app->storagePath('runtime/compiled_templates'));
    }
}
