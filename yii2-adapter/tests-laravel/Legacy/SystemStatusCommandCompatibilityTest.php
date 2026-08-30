<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

afterEach(function(): void {
    app()->maintenanceMode()->deactivate();
});

it('forwards the deprecated maintenance commands to Laravel', function(): void {
    Route::middleware('web')->get('legacy-maintenance-status', fn(): string => 'Application is live');

    $this->artisan('craft:off --retry=60')
        ->expectsOutputToContain('The `craft off` command is deprecated. Use `down` instead.')
        ->assertSuccessful();

    $this->get('/legacy-maintenance-status')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '60');

    $this->artisan('craft:on')
        ->expectsOutputToContain('The `craft on` command is deprecated. Use `up` instead.')
        ->assertSuccessful();

    $this->get('/legacy-maintenance-status')
        ->assertOk()
        ->assertSeeText('Application is live');
});
