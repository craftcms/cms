<?php

declare(strict_types=1);

namespace CraftCms\Cms\CriticalNotification;

use CraftCms\Cms\CriticalNotification\Checks\CriticalUpdateCheck;
use CraftCms\Cms\CriticalNotification\Checks\DebugInProductionCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class CriticalNotificationServiceProvider extends ServiceProvider
{
    public function boot(CriticalNotifications $criticalNotifications): void
    {
        $criticalNotifications->registerCheck(CriticalUpdateCheck::class);
        $criticalNotifications->registerCheck(DebugInProductionCheck::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule
                ->call(fn () => app(CriticalNotifications::class)->runChecks())
                ->name('craft:critical-notification-checks')
                ->daily()
                ->withoutOverlapping();
        });
    }
}
