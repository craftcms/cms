<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

it('warns when dropping all tables from an empty database', function () {
    withIsolatedSqliteConnectionForDropAllTables(function () {
        $exitCode = artisan('craft:db:drop-all-tables')
            ->expectsOutputToContain('No existing database tables found.')
            ->run();

        expect($exitCode)->toBe(0);
    });
});

it('drops all tables from an isolated database', function () {
    withIsolatedSqliteConnectionForDropAllTables(function (string $connectionName) {
        $schema = Schema::connection($connectionName);

        $schema->create('drop_me_parent', function (Blueprint $table) {
            $table->id();
        });

        $schema->create('drop_me_child', function (Blueprint $table) {
            $table->foreignId('drop_me_parent_id')->constrained('drop_me_parent');
        });

        expect($schema->hasTable('drop_me_parent'))->toBeTrue();
        expect($schema->hasTable('drop_me_child'))->toBeTrue();

        $exitCode = artisan('craft:db:drop-all-tables')
            ->expectsConfirmation('Are you sure you want to drop all tables from the database?', 'yes')
            ->expectsConfirmation('Backup your database?', 'no')
            ->expectsOutputToContain('Finished dropping all database tables.')
            ->run();

        expect($exitCode)->toBe(0);
        expect($schema->hasTable('drop_me_parent'))->toBeFalse();
        expect($schema->hasTable('drop_me_child'))->toBeFalse();
    });
});

/**
 * @param  callable(string):void  $callback
 */
function withIsolatedSqliteConnectionForDropAllTables(callable $callback): void
{
    $connectionName = 'drop_all_tables_'.uniqid();
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
