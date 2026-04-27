<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

it('guards convert-charset for non-mysql installs', function () {
    $exitCode = artisan('craft:db:convert-charset', [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ])
        ->expectsOutputToContain('This command is only available when using MySQL.')
        ->run();

    expect($exitCode)->toBe(1);
})->skip(
    fn () => in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true),
    'This assertion only applies to non-MySQL drivers.',
);
