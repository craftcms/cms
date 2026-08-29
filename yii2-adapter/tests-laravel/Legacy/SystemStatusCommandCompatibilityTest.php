<?php

declare(strict_types=1);

afterEach(function(): void {
    $this->artisan('up');
});

it('forwards the deprecated off command to Laravel maintenance mode', function(): void {
    $this->artisan('craft:off --retry=60')
        ->expectsOutputToContain('The `craft off` command is deprecated. Use `down` instead.')
        ->assertSuccessful();

    expect(app()->isDownForMaintenance())->toBeTrue()
        ->and(app()->maintenanceMode()->data()['retry'])->toBe(60);
});

it('forwards the deprecated on command to Laravel maintenance mode', function(): void {
    $this->artisan('down')->assertSuccessful();

    $this->artisan('craft:on')
        ->expectsOutputToContain('The `craft on` command is deprecated. Use `up` instead.')
        ->assertSuccessful();

    expect(app()->isDownForMaintenance())->toBeFalse();
});
