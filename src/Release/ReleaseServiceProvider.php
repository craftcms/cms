<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release;

use CraftCms\Cms\Edition;
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
                ->when(fn (): bool => Edition::get()->supportsReleases())
                ->name('craft:publish-releases')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }
}
