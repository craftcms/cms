<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use Craft;
use craft\test\TestSetup;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\Site;
use Dotenv\Dotenv;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

class TestCase extends Orchestra
{
    use RefreshDatabase;
    use WithWorkbench;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en-US');
        Config::set('app.timezone', 'America/Los_Angeles');

        Edition::set(Edition::Solo);

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Http::preventStrayRequests();

        $this->withoutVite();
    }

    protected function connectionsToTransact(): array
    {
        return [config('database.default'), 'db2'];
    }

    #[Override]
    protected function tearDown(): void
    {
        app(ProjectConfig::class)->reset();

        if (Craft::$app) {
            Craft::$app->getDb()->close();
            Craft::$app->getDb2()->close();
            DB::disconnect();

            TestSetup::tearDownCraft();
        }

        unset($_SERVER['CRAFT_SITE']);
        unset($_SERVER['CRAFT_SITE_UPPER']);

        parent::tearDown();
    }

    protected function migrateDatabases()
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        /** Drop Laravel migrations */
        Schema::drop('migrations');
        Schema::drop('cache');
        Schema::drop('sessions');
        Schema::drop('users');

        $site = new Site(
            name: 'Craft test site',
            handle: 'defaultSite',
            language: 'en-US',
            baseUrl: 'https://localhost/',
            primary: true,
            hasUrls: true,
        );

        $migration = new Install(
            username: 'craftcms',
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: $site,
        );

        Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();

        $migration->up();

        // Mark all existing migrations as applied
        $migrator = app(Migrator::class)->track('craft');
        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }
    }

    #[Override]
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make(ConfigRepository::class);

        $config->set('inertia.testing.page_paths', [__DIR__.'/../resources/js/pages']);
        $config->set('auth.defaults.guard', 'craft');

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));

        if (! file_exists(__DIR__.'/.env')) {
            return;
        }

        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $configKey = 'database.connections.'.env('DB_CONNECTION');

        $config->set($configKey, array_merge(
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
