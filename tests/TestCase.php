<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Database\ConnectionConfig;
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
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Tests\Support\DatabaseLock;
use CraftCms\Cms\Tests\Support\IsolatesParallelFiles;
use CraftCms\Cms\Tests\Support\RegistersPackageAliases;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use Pdo\Pgsql;
use ReflectionProperty;

class TestCase extends Orchestra
{
    use IsolatesParallelFiles;
    use RefreshDatabase;
    use RegistersPackageAliases;
    use WithWorkbench;

    private static bool $parallelDatabaseCreated = false;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (getenv('TEST_TOKEN') !== false) {
            return;
        }

        DatabaseLock::acquire();
    }

    #[Override]
    protected function setUp(): void
    {
        // This is so route registration in tests work
        $_SERVER['CRAFT_EDITION'] = Edition::Pro->handle();

        parent::setUp();

        config()->set('app.debug', true);

        app()->setLocale('en-US');
        app()->maintenanceMode()->deactivate();

        // Reset timezone to a consistent value for tests
        // This is needed because AppServiceProvider::setTimezone() runs during boot,
        // before RefreshDatabase has prepared the database, potentially reading stale data
        Cms::config()->timezone('America/Los_Angeles');
        Cms::setDefaultTimezone();

        // Tests run in Cp by default
        TemplateMode::set(TemplateMode::Cp);

        // MySQL InnoDB fulltext indexes are not transactional — data inserted
        // within a transaction is invisible to MATCH...AGAINST queries. Since
        // RefreshDatabase wraps each test in a transaction, disable fulltext
        // so searches fall back to LIKE and can see the test's own rows.
        if (DB::isMysql()) {
            app(Search::class)->useFullText = false;
        }

        File::cleanDirectory(config_path('craft/'.app(ProjectConfig::class)->folderName));

        File::cleanDirectory(storage_path('runtime/compiled_classes'));

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Http::preventStrayRequests();

        $this->withoutVite();

        // Always start with a fresh default admin user
        if ($user = User::first()) {
            $user->update([
                'username' => 'craftcms',
                'password' => Hash::make('craftcms2018!!'),
                'email' => 'support@craftcms.com',
                'admin' => true,
            ]);
        }
    }

    protected function connectionsToTransact(): array
    {
        if (config('database.default') === 'sqlite') {
            return [config('database.default')];
        }

        return [config('database.default'), 'db2'];
    }

    #[Override]
    protected function tearDown(): void
    {
        app()->maintenanceMode()->deactivate();
        Gate::clearResolvedInstances();
        PreventRequestsDuringMaintenance::flushState();

        app(ProjectConfig::class)->reset();

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

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
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
            )->silent();

            Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();

            $migrator = app(Migrator::class)->track('craft');
            $migrator->runMigration($migration, 'up');
            $migrator->getRepository()->log('Install', 1);

            // Mark all existing Craft migrations as applied
            foreach ($migrator->getPendingMigrations() as $file) {
                $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
            }

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        $projectConfigFolder = 'project';

        if (($token = getenv('TEST_TOKEN')) !== false) {
            $projectConfigFolder .= "_$token";
            $storagePath = $app->storagePath("parallel_$token");
            $app->useStoragePath($storagePath);
            File::ensureDirectoryExists($storagePath);
            File::ensureDirectoryExists($app->storagePath('framework'));
            File::ensureDirectoryExists($app->storagePath('framework/testing'));

            $app->afterResolving(ProjectConfig::class, function (ProjectConfig $projectConfig) use ($projectConfigFolder) {
                $projectConfig->folderName = $projectConfigFolder;
                $projectConfig->writeYamlAutomatically = false;
            });
        }

        File::cleanDirectory(config_path("craft/$projectConfigFolder"));

        File::cleanDirectory(storage_path('runtime/compiled_classes'));
        File::cleanDirectory(storage_path('logs'));

        $app->useEnvironmentPath(__DIR__);
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        tap($app->make(ConfigRepository::class), function (ConfigRepository $config) {
            $config->set('inertia.pages.paths', [__DIR__.'/../resources/js/pages']);

            $connection = env('DB_CONNECTION', 'testing');
            $driver = $config->get("database.connections.{$connection}.driver");

            $config->set('database.default', $connection);
            $config->set("database.connections.{$connection}.database", env('DB_DATABASE', database_path('craft_tests.sqlite')));
            $config->set("database.connections.{$connection}.host", env('DB_HOST', '127.0.0.1'));
            $config->set("database.connections.{$connection}.username", env('DB_USERNAME', 'root'));
            $config->set("database.connections.{$connection}.password", env('DB_PASSWORD', ''));
            $config->set("database.connections.{$connection}.charset", env('DB_CHARSET', in_array($driver, ['mysql', 'mariadb']) ? 'utf8mb4' : 'utf8'));
            $config->set("database.connections.{$connection}.collation", env('DB_COLLATION', in_array($driver, ['mysql', 'mariadb']) ? 'utf8mb4_unicode_ci' : 'utf8'));
            $config->set("database.connections.{$connection}.prefix", env('DB_PREFIX'));

            $connectionConfig = $config->get("database.connections.{$connection}");

            if (is_array($connectionConfig)) {
                $connectionConfig = ConnectionConfig::normalize($connectionConfig);

                if (($connectionConfig['driver'] ?? null) === 'sqlite') {
                    $connectionConfig['foreign_key_constraints'] = true;

                    unset(
                        $connectionConfig['busy_timeout'],
                        $connectionConfig['journal_mode'],
                        $connectionConfig['pragmas'],
                        $connectionConfig['synchronous'],
                    );

                    ConnectionConfig::ensureSqliteDatabaseFile((string) ($connectionConfig['database'] ?? ''));
                }

                if (($token = getenv('TEST_TOKEN')) !== false && ($database = $connectionConfig['database'] ?? null) !== ':memory:') {
                    $config->set("database.connections.{$connection}", $connectionConfig);
                    DB::setDefaultConnection($connection);

                    $parallelDatabase = "{$database}_test_{$token}";

                    if (! self::$parallelDatabaseCreated) {
                        DB::getSchemaBuilder()->dropDatabaseIfExists($parallelDatabase);
                        DB::getSchemaBuilder()->createDatabase($parallelDatabase);
                        self::$parallelDatabaseCreated = true;
                    }

                    $connectionConfig['database'] = $parallelDatabase;
                }

                $config->set("database.connections.{$connection}", $connectionConfig);
                DB::purge($connection);
            }

            if ($connection === 'pgsql') {
                $config->set("database.connections.{$connection}.options", [
                    Pgsql::ATTR_EMULATE_PREPARES => false,
                ]);
            }

            DB::setDefaultConnection($connection);
        });
    }
}
