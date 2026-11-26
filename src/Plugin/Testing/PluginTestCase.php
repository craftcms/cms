<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use CraftCms\Aliases\AliasesServiceProvider;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Models\User;
use CraftCms\DependencyAwareCache\CacheServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

/**
 * @since 6.0.0
 */
abstract class PluginTestCase extends BaseTestCase
{
    use InstallsPlugin;
    use LazilyRefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        // Testbench's default user model can't receive the notifications that control panel pages load
        $app->make(Repository::class)->set('auth.providers.users.model', User::class);

        $app->afterResolving(ProjectConfig::class, function (ProjectConfig $projectConfig): void {
            $projectConfig->writeYamlAutomatically = false;
        });
    }

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
            applyProjectConfigYaml: false,
        )->silent();

        $migrator = app(Migrator::class)->track('craft');
        $migrator->runMigration($migration, 'up');
        $migrator->getRepository()->log('Install', 1);

        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

    }

    /**
     * Craft's facade aliases, which plugin suites don't get from package discovery. Craft also exposes them to
     * templates as Twig globals, such as `Sites`. Testbench asks for them before the `files` binding exists, so this
     * reads `composer.json` without the `File` facade.
     *
     * Overrides should merge `parent::getPackageAliases($app)`. No return type, so overrides written for Testbench's
     * untyped method stay compatible.
     *
     * @return array<string, class-string>
     */
    #[Override]
    protected function getPackageAliases($app)
    {
        return Json::decode((string) file_get_contents(dirname(__DIR__, 3).'/composer.json'))['extra']['laravel']['aliases'];
    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            AliasesServiceProvider::class,
            CacheServiceProvider::class,
            InertiaServiceProvider::class,
            CraftServiceProvider::class,
        ];
    }
}
