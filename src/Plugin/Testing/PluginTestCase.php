<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\Cms\Site\Data\Site;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

abstract class PluginTestCase extends BaseTestCase
{
    use InstallsPlugin;
    use LazilyRefreshDatabase;

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
        )->silent();

        $migrator = app(Migrator::class)->track('craft');
        $migrator->runMigration($migration, 'up');
        $migrator->getRepository()->log('Install', 1);

        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

        app(LaravelMigrations::class)->install($migrator);
    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            CraftServiceProvider::class,
        ];
    }
}
