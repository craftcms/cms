<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Update\Updates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

test('upgrades provision a working database queue', function (bool $transitionPending) {
    Schema::drop('jobs');
    Schema::drop('job_batches');
    Schema::drop('failed_jobs');

    $pendingMigrations = ['0000_00_00_000000_create_laravel_tables'];

    if ($transitionPending) {
        $pendingMigrations[] = '0000_00_00_000007_migrate_to_laravel_queue';
    }

    DB::table(Table::MIGRATIONS)
        ->where('track', 'craft')
        ->whereIn('migration', $pendingMigrations)
        ->delete();

    app(Updates::class)->runMigrations(['craft']);

    $queue = Queue::connection('database');
    expect($queue->size())->toBe(0);

    $payload = json_encode(['job' => 'ExampleJob', 'data' => ['message' => 'Upgrade complete']], JSON_THROW_ON_ERROR);
    $queue->pushRaw($payload);

    DB::table(Table::MIGRATIONS)
        ->where('track', 'craft')
        ->where('migration', '0000_00_00_000000_create_laravel_tables')
        ->delete();

    app(Updates::class)->runMigrations(['craft']);

    expect($queue->size())->toBe(1)
        ->and($queue->pop()->getRawBody())->toBe($payload);
})->with([
    'pending queue transition' => true,
    'previously applied queue transition' => false,
]);

test('upgrades provision database cache and lock stores', function () {
    Schema::drop(Table::CACHE);
    Schema::drop('cache_locks');

    DB::table(Table::MIGRATIONS)
        ->where('track', 'craft')
        ->where('migration', '0000_00_00_000000_create_laravel_tables')
        ->delete();

    app(Updates::class)->runMigrations(['craft']);

    $cache = Cache::store('database');
    $cache->put('upgrade', 'complete', 60);

    expect($cache->get('upgrade'))->toBe('complete');

    $lock = $cache->lock('upgrade-lock', 60);
    expect($lock->get())->toBeTrue()
        ->and($cache->lock('upgrade-lock', 60)->get())->toBeFalse();

    $lock->release();
    expect($cache->lock('upgrade-lock', 60)->get())->toBeTrue();
});

test('password reset upgrades remove legacy verification columns when the token table already exists', function () {
    Schema::table(Table::USERS, function (Blueprint $table) {
        $table->string('verificationCode')->nullable();
        $table->dateTime('verificationCodeIssuedDate')->nullable();
    });

    DB::table(Table::MIGRATIONS)
        ->where('track', 'craft')
        ->whereIn('migration', [
            '0000_00_00_000000_create_laravel_tables',
            '0000_00_00_000006_create_password_reset_tokens_table',
        ])
        ->delete();

    app(Updates::class)->runMigrations(['craft']);

    expect(Schema::hasColumn(Table::USERS, 'verificationCode'))->toBeFalse()
        ->and(Schema::hasColumn(Table::USERS, 'verificationCodeIssuedDate'))->toBeFalse();
});
