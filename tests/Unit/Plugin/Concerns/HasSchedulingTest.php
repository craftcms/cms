<?php

declare(strict_types=1);

use CraftCms\Cms\Plugin\Plugins;
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

it('registers plugin scheduled tasks', function () {
    ScheduledTestPlugin::$configureSchedule = function (Schedule $schedule): void {
        $schedule
            ->command('plugin:test')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->timezone('UTC');
    };

    $plugin = ScheduledTestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasScheduling();

    $event = app(Schedule::class)->events()[0] ?? null;

    expect($event)->not()->toBeNull()
        ->and($event->getExpression())->toBe('0 2 * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->onOneServer)->toBeTrue()
        ->and($event->timezone)->toBe('UTC');
});

it('registers scheduled tasks immediately when the schedule has already been resolved', function () {
    $schedule = app(Schedule::class);

    ScheduledTestPlugin::$configureSchedule = function (Schedule $schedule): void {
        $schedule->command('plugin:test')->hourly();
    };

    $plugin = ScheduledTestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasScheduling();

    expect($schedule->events())->toHaveCount(1)
        ->and($schedule->events()[0]->getExpression())->toBe('0 * * * *');
});

it('does not register scheduled tasks when the plugin has no schedule hook', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasScheduling();

    expect(app(Schedule::class)->events())->toBeEmpty();
});

it('only registers scheduled tasks for installed and enabled plugins', function (bool $installed, bool $enabled, int $expectedEvents) {
    ScheduledTestPlugin::$configureSchedule = function (Schedule $schedule): void {
        $schedule->command('plugin:test')->daily();
    };

    $plugin = ScheduledTestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugins = Mockery::mock(Plugins::class);
    $plugins->shouldReceive('getPluginHandleByClass')
        ->with(ScheduledTestPlugin::class)
        ->once()
        ->andReturn('test-plugin');
    $plugins->shouldReceive('isPluginInstalled')
        ->with('test-plugin')
        ->once()
        ->andReturn($installed);
    $plugins->shouldReceive('isPluginEnabled')
        ->with('test-plugin')
        ->times($installed ? 1 : 0)
        ->andReturn($enabled);

    $plugin->boot($plugins);

    expect(app(Schedule::class)->events())->toHaveCount($expectedEvents);
})->with([
    'installed and enabled' => [true, true, 1],
    'installed and disabled' => [true, false, 0],
    'not installed' => [false, false, 0],
]);

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
