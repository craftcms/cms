<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class ReleaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule
                ->call(fn (): int => app(Releases::class)->publishDue())
                ->name('craft:publish-releases')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }
}
