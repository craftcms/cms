<?php

namespace Craft\Cms\Tests;

use Craft;
use Craft\Cms\Migrations\Install;
use Craft\Cms\Providers\CraftServiceProvider;
use craft\elements\User;
use craft\models\Site;
use craft\test\TestSetup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
