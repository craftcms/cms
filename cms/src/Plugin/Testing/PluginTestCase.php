<?php

namespace CraftCms\Cms\Plugin\Testing;

use craft\models\Site;
use CraftCms\Cms\Database\Migrations\Install;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class PluginTestCase extends BaseTestCase
{
    use InstallsPlugin;
    use LazilyRefreshDatabase;

    protected function migrateDatabases(): void
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        /** Install migration adds their own */
        Schema::drop('migrations');

        $siteConfig = [
            'name' => 'Craft test site',
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => 'https://localhost/',
            'language' => 'en-US',
            'primary' => true,
        ];

        $site = new Site($siteConfig);

        $migration = new Install(
            username: 'craftcms',
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: $site,
        );

        $migration->up();
    }

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            \CraftCms\Cms\Providers\CraftServiceProvider::class,
            \CraftCms\Yii2Adapter\Yii2ServiceProvider::class,
        ];
    }
}
