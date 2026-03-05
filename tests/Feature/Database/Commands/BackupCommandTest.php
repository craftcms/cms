<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

it('fails backup on sqlite because dumps are unsupported', function () {
    expect(fn () => artisan('craft:db:backup')->run())
        ->toThrow(\RuntimeException::class, 'Database backups are only supported for MySQL/MariaDB and PostgreSQL.');
})->skip(
    fn () => DB::connection()->getDriverName() !== 'sqlite',
    'This assertion only applies to sqlite.',
);
