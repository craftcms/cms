<?php

declare(strict_types=1);

use CraftCms\Cms\Database\DatabaseServiceProvider;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;

it('uses the write connection for console requests', function () {
    $db = DB::connection();
    $writePdo = new PDO('sqlite::memory:');
    $readPdo = new PDO('sqlite::memory:');
    $db->setPdo($writePdo);
    $db->setReadPdo($readPdo);
    $db->useWriteConnectionWhenReading(false);

    new DatabaseServiceProvider(app())->boot(
        app(Repository::class),
        $db,
        app(CacheRepository::class),
    );

    expect($db->getReadPdo())->toBe($writePdo);
});

it('introspects the read connection unless the connection reads from the write connection', function () {
    $databases = [];

    foreach (['read' => 'replicaColumn', 'write' => 'primaryColumn'] as $type => $column) {
        $databases[$type] = tempnam(sys_get_temp_dir(), 'craft-schema-');
        new PDO("sqlite:{$databases[$type]}")->exec("create table schema_test (id integer, $column text)");
    }

    config()->set('database.connections.split_schema_test', [
        'driver' => 'sqlite',
        'read' => ['database' => $databases['read']],
        'write' => ['database' => $databases['write']],
    ]);

    try {
        $connection = DB::connection('split_schema_test');

        expect($connection->getReadSchemaBuilder()->getColumnListing('schema_test'))->toBe(['id', 'replicaColumn']);

        $connection->useWriteConnectionWhenReading();

        expect($connection->getReadSchemaBuilder()->getColumnListing('schema_test'))->toBe(['id', 'primaryColumn']);
    } finally {
        DB::purge('split_schema_test');
        DB::purge('split_schema_test::read');
        array_map(unlink(...), $databases);
    }
});

it('commits transactions that were not committed implicitly', function () {
    config()->set('database.connections.implicit_commit_test', ['driver' => 'sqlite', 'database' => ':memory:']);

    try {
        $connection = DB::connection('implicit_commit_test');
        $connection->statement('create table implicit_commit_test (id integer)');

        $connection->beginTransaction();
        $connection->table('implicit_commit_test')->insert(['id' => 1]);
        $connection->commitSchemaChanges();

        expect($connection->transactionLevel())->toBe(0)
            ->and($connection->getPdo()->inTransaction())->toBeFalse()
            ->and($connection->table('implicit_commit_test')->count())->toBe(1);
    } finally {
        DB::purge('implicit_commit_test');
    }
});

it('completes the commit when the transaction was committed implicitly', function () {
    config()->set('database.connections.implicit_commit_test', ['driver' => 'sqlite', 'database' => ':memory:']);

    try {
        $connection = DB::connection('implicit_commit_test');
        $callbacks = [];

        $connection->beginTransaction();
        $connection->afterCommit(function () use (&$callbacks) {
            $callbacks[] = 'commit';
        });
        $connection->afterRollBack(function () use (&$callbacks) {
            $callbacks[] = 'rollBack';
        });
        $connection->getPdo()->commit();

        $connection->commitSchemaChanges();

        expect($connection->transactionLevel())->toBe(0)
            ->and($connection->getPdo()->inTransaction())->toBeFalse()
            ->and($callbacks)->toBe(['commit']);
    } finally {
        DB::purge('implicit_commit_test');
    }
});
