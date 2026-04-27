<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

it('drops a detected table prefix on an isolated database', function () {
    withIsolatedSqliteConnectionForDropTablePrefix(function (string $connectionName) {
        $schema = Schema::connection($connectionName);
        $tables = ['pref_elements', 'pref_entries', 'pref_info', 'pref_sections', 'pref_custom'];

        foreach ($tables as $tableName) {
            $schema->create($tableName, function (Blueprint $table) {
                $table->string('name')->nullable();
            });
        }

        $exitCode = artisan('craft:db:drop-table-prefix')
            ->expectsOutputToContain('Detecting the current table prefix ...')
            ->expectsOutputToContain('`pref` detected.')
            ->expectsConfirmation('Are you sure you want to proceed?', 'yes')
            ->expectsOutputToContain('Database tables renamed.')
            ->run();

        expect($exitCode)->toBe(0);

        $renamedTables = [
            'pref_elements' => 'elements',
            'pref_entries' => 'entries',
            'pref_info' => 'info',
            'pref_sections' => 'sections',
            'pref_custom' => 'custom',
        ];

        foreach ($renamedTables as $oldName => $newName) {
            expect($schema->hasTable($oldName))->toBeFalse();
            expect($schema->hasTable($newName))->toBeTrue();
        }
    });
});

it('fails dropping table prefix when no prefix is detected', function () {
    withIsolatedSqliteConnectionForDropTablePrefix(function (string $connectionName) {
        $schema = Schema::connection($connectionName);

        foreach (['elements', 'entries', 'info', 'sections'] as $tableName) {
            $schema->create($tableName, function (Blueprint $table) {
                $table->string('name')->nullable();
            });
        }

        $exitCode = artisan('craft:db:drop-table-prefix')
            ->expectsOutputToContain('No current table prefix appears to be in use.')
            ->run();

        expect($exitCode)->toBe(1);
    });
});

/**
 * @param  callable(string):void  $callback
 */
function withIsolatedSqliteConnectionForDropTablePrefix(callable $callback): void
{
    $connectionName = 'drop_table_prefix_'.uniqid();
    $databasePath = storage_path("runtime/$connectionName.sqlite");
    $container = app();

    File::ensureDirectoryExists(dirname($databasePath));
    File::delete($databasePath);
    File::put($databasePath, '');

    config()->set("database.connections.$connectionName", [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    DB::purge($connectionName);
    $connection = DB::connection($connectionName);
    $originalConnection = $container->make(Connection::class);
    $container->instance(Connection::class, $connection);

    try {
        $callback($connectionName);
    } finally {
        $container->instance(Connection::class, $originalConnection);
        DB::disconnect($connectionName);
        DB::purge($connectionName);
        config()->set("database.connections.$connectionName");
        File::delete($databasePath);
    }
}
