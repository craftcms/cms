<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function () {
    app()->forgetInstance(Schedule::class);
    app()->forgetInstance(ScheduledTestPlugin::class);
    ScheduledTestPlugin::$configureSchedule = null;
});

afterEach(function () {
    app()->forgetInstance(Schedule::class);
    app()->forgetInstance(ScheduledTestPlugin::class);
    ScheduledTestPlugin::$configureSchedule = null;
});

it('runs the schedule hook when the schedule is resolved', function () {
    $calls = 0;

    ScheduledTestPlugin::$configureSchedule = function () use (&$calls): void {
        $calls++;
    };

    $plugin = ScheduledTestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasScheduling();

    expect($calls)->toBe(0);

    app(Schedule::class);

    expect($calls)->toBe(1);
});

it('runs the schedule hook immediately when the schedule was already resolved', function () {
    app(Schedule::class);

    $calls = 0;

    ScheduledTestPlugin::$configureSchedule = function () use (&$calls): void {
        $calls++;
    };

    $plugin = ScheduledTestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasScheduling();

    expect($calls)->toBe(1);
});

it('does not register scheduled tasks when the plugin has no schedule hook', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasScheduling();

    expect(app(Schedule::class)->events())->toBeEmpty();
});

class ScheduledTestPlugin extends TestPlugin
{
    public static ?Closure $configureSchedule = null;

    protected function schedule(Schedule $schedule): void
    {
        if (self::$configureSchedule) {
            (self::$configureSchedule)($schedule);
        }
    }
}
