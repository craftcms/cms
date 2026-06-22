<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('stores sqlite credentials without server environment variables', function () {
    $basePath = storage_path('runtime/setup-db-creds-'.uniqid());
    $databasePath = "$basePath/craft.sqlite";
    $envPath = "$basePath/.env";
    $originalEnvironmentPath = app()->environmentPath();
    $originalDefaultConnection = config('database.default');
    $originalSqliteConfig = config('database.connections.sqlite');
    $originalDb2Config = config('database.connections.db2');

    File::ensureDirectoryExists($basePath);
    File::put($envPath, implode(PHP_EOL, [
        'DB_CONNECTION=mysql',
        'DB_HOST=127.0.0.1',
        'DB_PORT=3306',
        'DB_DATABASE=old_database',
        'DB_USERNAME=root',
        'DB_PASSWORD=secret',
        'DB_SCHEMA=public',
    ]));

    app()->useEnvironmentPath($basePath);

    try {
        $this->artisan('craft:setup:db-creds', [
            '--driver' => 'sqlite',
            '--database' => $databasePath,
            '--prefix' => '',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(File::isFile($databasePath))->toBeTrue()
            ->and(File::get($envPath))
            ->toContain('DB_CONNECTION=sqlite')
            ->toContain(sprintf('DB_DATABASE="%s"', $databasePath))
            ->toContain('DB_FOREIGN_KEYS=true')
            ->not->toContain('DB_HOST=')
            ->not->toContain('DB_PORT=')
            ->not->toContain('DB_USERNAME=')
            ->not->toContain('DB_PASSWORD=')
            ->not->toContain('DB_SCHEMA=');
    } finally {
        DB::setDefaultConnection($originalDefaultConnection);
        config()->set('database.default', $originalDefaultConnection);
        config()->set('database.connections.sqlite', $originalSqliteConfig);
        config()->set('database.connections.db2', $originalDb2Config);
        DB::purge('sqlite');
        DB::purge('db2');
        app()->useEnvironmentPath($originalEnvironmentPath);
        File::deleteDirectory($basePath);
    }
});
