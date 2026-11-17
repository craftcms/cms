<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

it('completes the commit after a schema change implicitly commits a transaction', function () {
    config()->set('database.connections.implicit_commit_test', config('database.connections.'.DB::getDefaultConnection()));
    $connection = DB::connection('implicit_commit_test');

    $callbacks = [];

    try {
        $connection->beginTransaction();
        $connection->afterCommit(function () use (&$callbacks) {
            $callbacks[] = 'commit';
        });
        $connection->afterRollBack(function () use (&$callbacks) {
            $callbacks[] = 'rollBack';
        });
        $connection->getSchemaBuilder()->create('implicit_commit_test', function (Blueprint $table) {
            $table->id();
        });

        expect($connection->getPdo()->inTransaction())->toBeFalse();

        $connection->commitSchemaChanges();

        expect($connection->transactionLevel())->toBe(0)
            ->and($connection->getPdo()->inTransaction())->toBeFalse()
            ->and($callbacks)->toBe(['commit']);
    } finally {
        $connection->getSchemaBuilder()->dropIfExists('implicit_commit_test');
        DB::purge('implicit_commit_test');
    }
})->skip(fn () => ! DB::isMysql(), 'Only MySQL implicitly commits transactions on schema changes.');
