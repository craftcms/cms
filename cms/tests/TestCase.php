<?php

namespace Craft\Cms\Tests;

use Craft;
use Craft\Cms\Migrations\Install;
use Craft\Cms\Providers\CraftServiceProvider;
use craft\elements\User;
use craft\models\Site;
use craft\test\TestSetup;
use Dotenv\Dotenv;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as Orchestra;

use function Orchestra\Testbench\artisan;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User([
            'active' => true,
            'admin' => true,
            'username' => 'craftcms',
            'newPassword' => 'password',
            'email' => 'support@craftcms.com',
        ]);

        Craft::$app->getElements()->saveElement($user);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Craft\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function migrateDatabases()
    {
        $app = app('Craft');

        if ($app instanceof \Craft) {
            $app = $app::$app;
        }

        TestSetup::cleanseDb($app->getDb());

        $siteConfig = [
            'name' => 'Craft test site',
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => 'https://test.craftcms.test/',
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

        // $this->artisan('migrate:fresh', $this->migrateFreshUsing());
    }

    protected function getPackageProviders($app): array
    {
        return [
            CraftServiceProvider::class,
            Craft\Yii2Adapter\Yii2ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (! file_exists(__DIR__.'/../.env')) {
            return;
        }

        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        $configKey = "database.connections." . env('CRAFT_DB_DRIVER');

        $app['config']->set($configKey, array_merge(
            Config::array($configKey, []),
            [
                'host' => env('CRAFT_DB_SERVER'),
                'port' => env('CRAFT_DB_PORT'),
                'database' => env('CRAFT_DB_DATABASE'),
                'username' => env('CRAFT_DB_USER'),
                'password' => env('CRAFT_DB_PASSWORD'),
                'prefix' => env('CRAFT_DB_PREFIX'),
                'charset' => env('CRAFT_DB_CHARSET'),
            ]),
        );

        DB::setDefaultConnection(env('CRAFT_DB_DRIVER'));
    }
}
