<?php

namespace CraftCms\Cms\Deprecator;

use CraftCms\Cms\Deprecator\Commands\ClearDeprecations;
use Illuminate\Support\ServiceProvider;

final class DeprecatorServiceProvider extends ServiceProvider
{
    public function boot(Deprecator $deprecator): void
    {
        $this->app->terminating(fn () => $deprecator->storeLogs());

        $this->commands([
            ClearDeprecations::class,
        ]);
    }
}
