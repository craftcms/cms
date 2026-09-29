<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Commands\MigrateCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Yaml\Yaml;

use function Pest\Laravel\artisan;

afterEach(function () {
    MigrateCommand::flushState();
});

afterAll(function () {
    RefreshDatabaseState::$migrated = false;
});

it('runs migrations', function () {
    DB::table(Table::MIGRATIONS)->delete();

    expect(DB::table(Table::MIGRATIONS)->count())->toBe(0);

    artisan('craft:migrate:all')
        ->expectsOutputToContain('Checking for pending migrations')
        ->expectsOutputToContain('new Craft migrations to be applied:')
        ->expectsConfirmation('Apply the above migrations?', 'yes')
        ->expectsOutputToContain('Application is now in maintenance mode.')
        ->expectsConfirmation('Create database backup?', 'no')
        ->expectsOutputToContain('Application is now live.')
        ->run();

    expect(DB::table(Table::MIGRATIONS)->count())->toBeGreaterThan(0);
});

it('adds the migration track column before checking pending migrations', function () {
    Schema::dropIfExists(Table::MIGRATIONS);
    Schema::create(Table::MIGRATIONS, function (Blueprint $table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });

    artisan('craft:migrate:all', [
        '--force' => true,
        '--no-backup' => true,
        '--track' => 'craft',
    ])
        ->expectsConfirmation('Apply the above migrations?', 'yes')
        ->assertSuccessful();

    expect(Schema::hasColumn(Table::MIGRATIONS, 'track'))->toBeTrue();
});

it('runs additional migrators', function () {
    $migrator = Mockery::mock(Migrator::class);
    $migrator->expects('getTrack')->andReturn('custom');
    $migrator->expects('getPendingMigrations')->andReturn(['2026_01_01_000000_custom']);
    $migrator->allows('setOutput')->andReturnSelf();
    $migrator->allows('getMigrationName')->andReturn('Custom migration');
    $migrator->expects('run')->once()->andReturn([]);

    MigrateCommand::registerMigrator(fn () => $migrator);

    artisan('craft:migrate:all', [
        '--force' => true,
        '--no-backup' => true,
        '--no-interaction' => true,
        '--track' => 'custom',
    ])->assertSuccessful();
});

it('skips additional migrators without a track', function () {
    $migrator = Mockery::mock(Migrator::class);
    $migrator->expects('getTrack')->andReturnNull();

    MigrateCommand::registerMigrator(fn () => $migrator);

    artisan('craft:migrate:all', [
        '--force' => true,
        '--no-backup' => true,
        '--track' => 'custom',
    ])
        ->expectsOutputToContain('A migrator was registered without a track.')
        ->assertSuccessful();
});

it('allows project config changes without overwriting pending YAML changes', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->regenerateExternalConfig();
    $projectConfig->readOnly = true;
    $projectConfig->writeYamlAutomatically = true;

    $file = Path::config($projectConfig->folderName).'/'.ProjectConfig::CONFIG_FILENAME;
    $yaml = Yaml::parseFile($file);
    $yaml['system']['name'] = 'Pending Name';
    file_put_contents($file, Yaml::dump($yaml, 20));

    artisan('craft:migrate:all', [
        '--force' => true,
        '--no-backup' => true,
        '--track' => 'custom',
    ])->assertSuccessful();

    expect($projectConfig->readOnly)->toBeFalse()
        ->and($projectConfig->writeYamlAutomatically)->toBeFalse()
        ->and(Yaml::parseFile($file)['system']['name'])->toBe('Pending Name');
});

it('writes out the project config YAML before migrating if it does not exist', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->readOnly = true;
    $projectConfig->writeYamlAutomatically = true;

    expect($projectConfig->getDoesExternalConfigExist())->toBeFalse();

    artisan('craft:migrate:all', [
        '--force' => true,
        '--no-backup' => true,
        '--track' => 'custom',
    ])->assertSuccessful();

    expect($projectConfig->getDoesExternalConfigExist())->toBeTrue()
        ->and($projectConfig->readOnly)->toBeTrue()
        ->and($projectConfig->writeYamlAutomatically)->toBeTrue();
});
