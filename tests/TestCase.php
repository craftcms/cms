<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use Craft;
use craft\test\TestSetup;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\View\TemplateMode;
use Dotenv\Dotenv;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use ReflectionProperty;

class TestCase extends Orchestra
{
    use RefreshDatabase;
    use WithWorkbench;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en-US');

        // Reset timezone to a consistent value for tests
        // This is needed because AppServiceProvider::setTimezone() runs during boot,
        // before RefreshDatabase has prepared the database, potentially reading stale data
        Config::set('app.timezone', 'America/Los_Angeles');
        date_default_timezone_set('America/Los_Angeles');

        // Tests run in Pro and Cp by default
        Edition::set(Edition::Pro);
        TemplateMode::set(TemplateMode::Cp);

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Http::preventStrayRequests();

        $this->withoutVite();

        // Always start with a fresh default admin user
        User::first()->update([
            'username' => 'craftcms',
            'password' => Hash::make('craftcms2018!!'),
            'email' => 'support@craftcms.com',
            'admin' => true,
        ]);
    }

    protected function connectionsToTransact(): array
    {
        return [config('database.default'), 'db2'];
    }

    #[Override]
    protected function tearDown(): void
    {
        Gate::clearResolvedInstances();

        app(ProjectConfig::class)->reset();

        DB::rollBack(0);

        if (Craft::$app) {
            Craft::$app->getDb()->close();
            Craft::$app->getDb2()->close();
            DB::disconnect();

            TestSetup::tearDownCraft();
        }

        self::resetStaticCaches();

        unset($_SERVER['CRAFT_SITE']);
        unset($_SERVER['CRAFT_SITE_UPPER']);

        parent::tearDown();
    }

    /**
     * Reset static caches that accumulate across tests, preventing memory leaks.
     */
    private static function resetStaticCaches(): void
    {
        // Flush Macroable macros from base classes
        Element::flushMacros();
        Field::flushMacros();
        FieldLayoutComponent::flushMacros();
        Widget::flushMacros();

        // ElementQuery has its own $macros property (not via Macroable trait)
        new ReflectionProperty(ElementQuery::class, 'macros')->setValue(null, []);

        // Reset static array caches that grow unboundedly across tests
        $resets = [
            [Element::class, 'sources', []],
            [BaseElementLinkType::class, 'fetchedElements', []],
            [Typecast::class, 'types', []],
            [PHP::class, 'basePaths', []],
            [FieldLayoutComponent::class, 'defaultElementConditions', []],
            [CustomField::class, 'defaultElementEditConditions', []],
        ];

        // Reset ProjectConfig "processed" flags
        $projectConfigFlags = [
            '_processedFilesystems',
            '_processedFields',
            '_processedSites',
            '_processedUserGroups',
            '_processedEntryTypes',
            '_processedSections',
            '_processedGqlSchemas',
        ];

        foreach ($projectConfigFlags as $flag) {
            $resets[] = [ProjectConfigHelper::class, $flag, false];
        }

        foreach ($resets as [$class, $property, $default]) {
            new ReflectionProperty($class, $property)->setValue(null, $default);
        }
    }

    protected function migrateDatabases(): void
    {
        // Clear any stale cached info from previous test runs
        // This must happen before db:wipe to ensure fresh state
        Context::forgetHidden('craft.info');
        Context::forgetHidden('craft.isInstalled');

        $this->artisan('db:wipe');

        $site = new Site([
            'name' => 'Craft test site',
            'handle' => 'defaultSite',
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

        $dotenv = Dotenv::createMutable(__DIR__);
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
                'charset' => env('DB_CHARSET', in_array($configKey, ['mysql', 'mariadb']) ? 'utf8mb4' : 'utf8'),
                'collation' => env('DB_COLLATION', in_array($configKey, ['mysql', 'mariadb']) ? 'utf8mb4_unicode_ci' : null),
                'prefix' => env('DB_PREFIX'),
            ]),
        );

        DB::setDefaultConnection(env('DB_CONNECTION'));
    }
}
