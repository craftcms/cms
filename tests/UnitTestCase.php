<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Path;
use CraftCms\Cms\Tests\Support\IsolatesParallelFiles;
use CraftCms\Cms\Tests\Support\RegistersPackageAliases;
use CraftCms\Cms\Twig\Twig;
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

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));
        File::cleanDirectory(storage_path('runtime/compiled_templates'));

        Cms::setIsInstalled(false);

        Edition::set(Edition::Pro);
        TemplateMode::set(TemplateMode::Cp);

        Sites::setCurrentSite(new Site);

        app()->setLocale('en-US');

        Cms::config()->timezone('America/Los_Angeles');
        Cms::setDefaultTimezone();

        if (($token = getenv('TEST_TOKEN')) !== false) {
            $compiledTemplatesPath = storage_path("runtime/compiled_templates_$token");

            Cms::config()->compiledTemplatesPath($compiledTemplatesPath);
            app()->forgetInstance(Path::class);
            app()->forgetInstance(Twig::class);
            File::ensureDirectoryExists($compiledTemplatesPath);
            File::cleanDirectory($compiledTemplatesPath);
        }
    }
}
