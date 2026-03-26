<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\Tests\FakeMigrator;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/plugins'));
});

it('runs the install migration and logs pending migrations', function () {
    $migrator = new FakeMigrator;
    $migrator->pendingMigrations = [
        '/tmp/m2026_01_01_000001_create_table.php',
    ];

    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->useMigrator($migrator);

    $plugin->install();

    expect($plugin->didCallBeforeInstall)->toBeTrue()
        ->and($plugin->didCallAfterInstall)->toBeTrue()
        ->and($plugin->isInstalled)->toBeTrue()
        ->and($migrator->runMigrationArgument)->toBeInstanceOf(Migration::class)
        ->and($migrator->runMigrationMethod)->toBe('up')
        ->and($migrator->loggedMigrations)->toBe([
            ['Install', 1],
            ['m2026_01_01_000001_create_table', 1],
        ]);
});

it('resets migrations on uninstall when an install migration exists', function () {
    $migrator = new FakeMigrator;

    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->useMigrator($migrator);

    $plugin->uninstall();

    expect($plugin->didCallBeforeUninstall)->toBeTrue()
        ->and($plugin->didCallAfterUninstall)->toBeTrue()
        ->and($migrator->resetArguments)->toBe([
            [dirname(__DIR__, 3).'/TestClasses/TestPlugin/database/migrations/Install.php'],
            [dirname(__DIR__, 3).'/TestClasses/TestPlugin/database/migrations'],
            false,
        ]);
});

it('prefers the Laravel migrations path when both paths exist', function () {
    $migrator = new FakeMigrator;
    $pluginRoot = storage_path('framework/testing/plugins/prioritized-plugin');
    $legacyPath = $pluginRoot.'/src/migrations';
    $laravelPath = $pluginRoot.'/database/migrations';

    File::ensureDirectoryExists($legacyPath);
    File::ensureDirectoryExists($laravelPath);
    File::put($legacyPath.'/Install.php', <<<'PHP'
<?php

return new class extends \CraftCms\Cms\Database\Migration
{
    public function up(): void
    {
    }
};
PHP);
    File::put($laravelPath.'/Install.php', <<<'PHP'
<?php

return new class extends \CraftCms\Cms\Database\Migration
{
    public function up(): void
    {
    }
};
PHP);

    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath($pluginRoot.'/src');
    $plugin->useMigrator($migrator);

    expect($plugin->getMigrationsPath())->toBe($laravelPath);

    $plugin->uninstall();

    expect($migrator->resetArguments)->toBe([
        [$laravelPath.'/Install.php'],
        [$laravelPath],
        false,
    ]);

    File::deleteDirectory($pluginRoot);
});
