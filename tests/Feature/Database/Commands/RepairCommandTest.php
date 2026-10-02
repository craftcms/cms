<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('warns when repairing an empty database', function () {
    withIsolatedSqliteConnectionForRepair(function () {
        $exitCode = artisan('craft:db:repair')
            ->expectsOutputToContain('No existing database tables found.')
            ->run();

        expect($exitCode)->toBe(0);
    });
});

/**
 * @param  callable(string):void  $callback
 */
function withIsolatedSqliteConnectionForRepair(callable $callback): void
{
    $connectionName = 'repair_'.uniqid();
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
