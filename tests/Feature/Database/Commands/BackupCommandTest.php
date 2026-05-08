<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('creates backups for sqlite file-backed databases', function () {
    withIsolatedSqliteConnectionForBackupCommand(function () {
        DB::connection()->statement('create table craft_entries (id integer primary key, title text)');
        DB::connection()->insert('insert into craft_entries (title) values (?)', ['SQLite backup']);

        $backupPath = storage_path('runtime/sqlite-command-backup-'.uniqid('', true).'.sql');

        $exitCode = artisan('craft:db:backup', [
            'path' => $backupPath,
            '--overwrite' => true,
        ])
            ->expectsOutputToContain('Database backup completed.')
            ->run();

        expect($exitCode)->toBe(0);
        expect(File::isFile($backupPath))->toBeTrue();
        expect(File::get($backupPath))->toContain('CREATE TABLE craft_entries');
        expect(File::get($backupPath))->toContain('INSERT INTO craft_entries');

        File::delete($backupPath);
    });
});

function withIsolatedSqliteConnectionForBackupCommand(callable $callback): void
{
    $connectionName = 'backup_command_'.uniqid();
    $databasePath = storage_path("runtime/$connectionName.sqlite");
    $originalDefaultConnection = config('database.default');

    File::ensureDirectoryExists(dirname($databasePath));
    File::delete($databasePath);
    File::put($databasePath, '');

    config()->set("database.connections.$connectionName", [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => 'craft_',
        'foreign_key_constraints' => true,
    ]);

    DB::purge($connectionName);
    DB::setDefaultConnection($connectionName);

    try {
        $callback();
    } finally {
        DB::setDefaultConnection($originalDefaultConnection);
        DB::disconnect($connectionName);
        DB::purge($connectionName);
        config()->set("database.connections.$connectionName");
        File::delete($databasePath);
    }
}
