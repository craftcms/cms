<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

it('runs migrations', function () {
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
