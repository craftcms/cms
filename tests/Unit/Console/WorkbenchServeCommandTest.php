<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('rejects an invalid workbench server port', function () {
    $this->artisan('workbench:serve', ['--port' => 'invalid'])
        ->expectsOutputToContain('The port must be an integer between 1 and 65535.')
        ->assertFailed();
});

it('offers an explicit fresh database option', function () {
    expect(Artisan::all()['workbench:serve']->getDefinition()->hasOption('fresh'))->toBeTrue();
});

it('rejects an invalid workbench URL', function () {
    $this->artisan('workbench:serve', ['--url' => 'ftp://example.com'])
        ->expectsOutputToContain('The URL must be a valid HTTP or HTTPS URL.')
        ->assertFailed();
});
