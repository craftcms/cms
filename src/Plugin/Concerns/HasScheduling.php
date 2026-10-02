<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasScheduling
{
    public function bootHasScheduling(): void
    {
        $callback = fn (Schedule $schedule) => $this->schedule($schedule);

        $this->app->afterResolving(Schedule::class, $callback);

        if ($this->app->resolved(Schedule::class)) {
            $callback($this->app->make(Schedule::class));
        }
    }

    protected function schedule(Schedule $schedule): void {}
}
