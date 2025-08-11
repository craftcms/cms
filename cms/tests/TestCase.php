<?php

namespace CraftCms\Cms\Tests;

use Craft;
use craft\models\Site;
use craft\services\ProjectConfig;
use craft\test\TestSetup;
use CraftCms\Cms\Migrations\Install;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\Cms\Support\Facades\Http;
use CraftCms\DependencyAwareCache\CacheServiceProvider;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

/** @since 6.0.0 */
class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Craft::$app->mutex->release(ProjectConfig::MUTEX_NAME);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if (Craft::$app) {
            Craft::$app->getProjectConfig()->flush();
            Craft::$app->getDb()->close();
            Craft::$app->getDb2()->close();
            DB::disconnect();

            TestSetup::tearDownCraft();
        }

        parent::tearDown();
    }

    protected function refreshTestDatabase()
    {
        if (RefreshDatabaseState::$migrated) {
            return;
        }

        $this->migrateDatabases();

        $this->app[Kernel::class]->setArtisan(null);

        RefreshDatabaseState::$migrated = true;
    }

    protected function migrateDatabases()
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

    protected function getPackageProviders($app): array
    {
        return [
            CacheServiceProvider::class,
            CraftServiceProvider::class,
            Yii2ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        File::cleanDirectory(config_path('craft/project'));

        if (! file_exists(__DIR__.'/../.env')) {
            return;
        }

        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        $configKey = 'database.connections.'.env('DB_CONNECTION');

        $app['config']->set($configKey, array_merge(
            Config::array($configKey, []),
            [
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'prefix' => env('DB_PREFIX'),
                'charset' => env('DB_CHARSET'),
            ]),
        );

        DB::setDefaultConnection(env('DB_CONNECTION'));
    }
}
