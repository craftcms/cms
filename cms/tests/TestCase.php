<?php

namespace CraftCms\Cms\Tests;

use Craft;
use craft\models\Site;
use craft\test\TestSetup;
use CraftCms\Aliases\AliasesServiceProvider;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\DependencyAwareCache\CacheServiceProvider;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
use Dotenv\Dotenv;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Tinker\TinkerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/** @since 6.0.0 */
class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Edition::set(Edition::Solo);

        Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
        File::cleanDirectory(config_path('project'));

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Http::preventStrayRequests();
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (Craft::$app) {
            app(ProjectConfig::class)->flush();
            Craft::$app->getDb()->close();
            Craft::$app->getDb2()->close();
            DB::disconnect();

            TestSetup::tearDownCraft();
        }

        parent::tearDown();
    }

    protected function migrateDatabases()
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        /** Install migration adds their own */
        Schema::drop('migrations');

        $siteConfig = [
            'name' => 'Craft test site',
            'handle' => 'defaultSite',
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
            AliasesServiceProvider::class,
            CacheServiceProvider::class,
            CraftServiceProvider::class,
            Yii2ServiceProvider::class,
            TinkerServiceProvider::class, // phpstan fails without it?
        ];
    }

    #[\Override]
    protected function getEnvironmentSetUp($app)
    {
        File::cleanDirectory(config_path('craft/project'));

        if (! file_exists(__DIR__.'/../../tests/.env')) {
            return;
        }

        $dotenv = Dotenv::createImmutable(__DIR__.'/../../tests');
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
                'charset' => env('DB_CHARSET', $configKey === 'mysql' ? 'utf8mb4' : 'utf8'),
                'collation' => env('DB_COLLATION', $configKey === 'mysql' ? 'utf8mb4_unicode_ci' : null),
                'prefix' => env('DB_PREFIX'),
            ]),
        );

        DB::setDefaultConnection(env('DB_CONNECTION'));
    }
}
