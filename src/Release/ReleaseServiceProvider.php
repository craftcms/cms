<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Update\Updates;
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
                ->when(fn (): bool => Cms::isInstalled()
                    && Edition::get()->supportsReleases()
                    && ! app(Updates::class)->isUpdatePending())
                ->name('craft:publish-releases')
                ->everyMinute()
                ->withoutOverlapping(5);
        });
    }
}
