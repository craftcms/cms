<?php

declare(strict_types=1);

use CraftCms\Cms\Database\ConnectionConfig;
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
            'database' => base_path('database/craft.sqlite'),
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

it('builds sqlite requirement checker dsn values from the connection config', function () {
    config()->set('database.connections.sqlite_dsn_test', [
        'driver' => 'sqlite',
        'database' => base_path('database/craft.sqlite'),
    ]);

    $connection = DB::connection('sqlite_dsn_test');

    expect(ConnectionConfig::dsn($connection))->toBe('sqlite:'.base_path('database/craft.sqlite'));

    DB::purge('sqlite_dsn_test');
});
