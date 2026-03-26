<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
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
        );

        $migration->up();
        app(LaravelMigrations::class)->ensureSessionsTable();
    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            CraftServiceProvider::class,
            Yii2ServiceProvider::class,
        ];
    }
}
