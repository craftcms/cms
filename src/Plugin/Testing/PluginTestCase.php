<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use CraftCms\Aliases\AliasesServiceProvider;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\DependencyAwareCache\CacheServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

/**
 * @since 6.0.0
 */
abstract class PluginTestCase extends BaseTestCase
{
    use InstallsPlugin;
    use LazilyRefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        $app->afterResolving(ProjectConfig::class, function (ProjectConfig $projectConfig): void {
            $projectConfig->writeYamlAutomatically = false;
        });
    }

    protected function migrateDatabases(): void
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        /** Install migration adds their own */
        Schema::drop('migrations');

        $site = new Site([
            'name' => 'Craft test site',
            'handle' => 'default',
            'language' => 'en-US',
            'baseUrl' => 'https://localhost/',
            'primary' => true,
            'hasUrls' => true,
        ]);

        $migration = new Install(
            username: 'craftcms',
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: $site,
            applyProjectConfigYaml: false,
        )->silent();

        $migrator = app(Migrator::class)->track('craft');
        $migrator->runMigration($migration, 'up');
        $migrator->getRepository()->log('Install', 1);

        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            AliasesServiceProvider::class,
            CacheServiceProvider::class,
            InertiaServiceProvider::class,
            CraftServiceProvider::class,
        ];
    }
}
