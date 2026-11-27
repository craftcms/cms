<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\SchedulerHeartbeat;
use Illuminate\Support\Facades\Date;

afterEach(function () {
    Date::setTestNow();
    app()->maintenanceMode()->deactivate();
});

it('reports the scheduler as running until three minutes after its last run, and when it last ran', function () {
    expect(SchedulerHeartbeat::isRunning())->toBeFalse()
        ->and(SchedulerHeartbeat::lastRanAt())->toBeNull();

    Date::setTestNow('2026-10-01 12:00:00');
    $this->artisan('schedule:run')->assertSuccessful();

    Date::setTestNow('2026-10-01 12:02:59');
    expect(SchedulerHeartbeat::isRunning())->toBeTrue();

    Date::setTestNow('2026-10-01 12:03:01');
    expect(SchedulerHeartbeat::isRunning())->toBeFalse();

    Date::setTestNow('2026-10-01 14:00:00');
    expect(SchedulerHeartbeat::isRunning())->toBeFalse()
        ->and(SchedulerHeartbeat::lastRanAt()?->toDateTimeString())->toBe('2026-10-01 12:00:00');
});

it('keeps reporting the scheduler as running while the system is offline or the scheduler is paused', function () {
    app()->maintenanceMode()->activate([]);
    $this->artisan('schedule:pause')->assertSuccessful();

    $this->artisan('schedule:run')->assertSuccessful();

    expect(SchedulerHeartbeat::isRunning())->toBeTrue();
});
