<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

it('runs migrations', function () {
    DB::table(Table::MIGRATIONS)->delete();

    expect(DB::table(Table::MIGRATIONS)->count())->toBe(0);

    artisan('craft:migrate:all')
        ->expectsOutputToContain('Checking for pending migrations')
        ->expectsOutputToContain('new Craft migrations to be applied:')
        ->expectsConfirmation('Apply the above migrations?', 'yes')
        ->expectsOutputToContain('Application is now in maintenance mode.')
        ->expectsConfirmation('Create database backup?', 'no')
        ->expectsOutputToContain('Application is now live.')
        ->run();

    expect(DB::table(Table::MIGRATIONS)->count())->toBeGreaterThan(0);
});

it('adds the migration track column before checking pending migrations', function () {
    Schema::dropIfExists(Table::MIGRATIONS);
    Schema::create(Table::MIGRATIONS, function (Blueprint $table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });

    artisan('craft:migrate:all', [
        '--force' => true,
        '--no-backup' => true,
        '--track' => 'craft',
    ])
        ->expectsConfirmation('Apply the above migrations?', 'yes')
        ->assertSuccessful();

    expect(Schema::hasColumn(Table::MIGRATIONS, 'track'))->toBeTrue();
});
