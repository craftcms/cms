<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('fails restore when backup path is missing', function () {
    $missingPath = base_path('storage/runtime/non-existent-backup-'.uniqid('', true).'.sql');

    $exitCode = artisan('craft:db:restore', ['path' => $missingPath])
        ->expectsOutputToContain("Backup path doesn't exist:")
        ->run();

    expect($exitCode)->toBe(1);
});
