<?php

declare(strict_types=1);

use CraftCms\Cms\Database\ConnectionConfig;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\DB;

it('normalizes file-backed sqlite connection config for production use', function () {
    $config = ConnectionConfig::normalize([
        'driver' => 'sqlite',
        'database' => 'database/craft.sqlite',
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'secret',
        'schema' => 'public',
    ]);

    expect($config)
        ->toMatchArray([
            'driver' => 'sqlite',
            'database' => ConnectionConfig::normalizeSqliteDatabasePath('database/craft.sqlite'),
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
            'pragmas' => [
                'cache_size' => -20000,
                'mmap_size' => 2147483648,
                'temp_store' => 'MEMORY',
            ],
            'synchronous' => 'normal',
        ])
        ->and(array_key_exists('host', $config))->toBeFalse()
        ->and(array_key_exists('port', $config))->toBeFalse()
        ->and(array_key_exists('username', $config))->toBeFalse()
        ->and(array_key_exists('password', $config))->toBeFalse()
        ->and(array_key_exists('schema', $config))->toBeFalse();
});

it('preserves custom sqlite runtime pragmas', function () {
    $config = ConnectionConfig::normalize([
        'driver' => 'sqlite',
        'database' => 'database/craft.sqlite',
        'pragmas' => [
            'cache_size' => -10000,
            'temp_store' => 'FILE',
        ],
        'synchronous' => 'full',
    ]);

    expect($config)
        ->toMatchArray([
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
            'pragmas' => [
                'cache_size' => -10000,
                'mmap_size' => 2147483648,
                'temp_store' => 'FILE',
            ],
            'synchronous' => 'full',
        ]);
});

it('leaves in-memory sqlite connections without file-backed pragmas', function () {
    expect(ConnectionConfig::normalize([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]))->toMatchArray([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'foreign_key_constraints' => true,
    ])->not->toHaveKeys(['busy_timeout', 'journal_mode', 'pragmas', 'synchronous']);

    expect(ConnectionConfig::normalize([
        'driver' => 'sqlite',
        'database' => 'file:craft?mode=memory&cache=shared',
    ]))->toMatchArray([
        'driver' => 'sqlite',
        'database' => 'file:craft?mode=memory&cache=shared',
        'foreign_key_constraints' => true,
    ])->not->toHaveKeys(['busy_timeout', 'journal_mode', 'pragmas', 'synchronous']);
});

it('normalizes relative sqlite database paths from the project base path', function () {
    expect(ConnectionConfig::normalizeSqliteDatabasePath('database.sqlite'))
        ->toBe(base_path('database.sqlite'));
});

it('preserves absolute sqlite database paths', function (string $path) {
    expect(ConnectionConfig::normalizeSqliteDatabasePath($path))->toBe(File::normalizePath($path));
})->with([
    'unix path' => ['/foo/database.sqlite'],
    'windows drive path' => ['C:\foo\database.sqlite'],
    'lowercase windows drive path' => ['c:\foo\database.sqlite'],
    'windows unc path' => ['\\\\server\\share\\database.sqlite'],
]);

it('builds sqlite requirement checker dsn values from the connection config', function () {
    config()->set('database.connections.sqlite_dsn_test', [
        'driver' => 'sqlite',
        'database' => base_path('database/craft.sqlite'),
    ]);

    $connection = DB::connection('sqlite_dsn_test');

    expect(ConnectionConfig::dsn($connection))->toBe('sqlite:'.ConnectionConfig::normalizeSqliteDatabasePath('database/craft.sqlite'));

    DB::purge('sqlite_dsn_test');
});
