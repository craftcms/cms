<?php

namespace CraftCms\Cms\Deprecator;

use CraftCms\Cms\Deprecator\Commands\ClearDeprecations;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
final class DeprecatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->terminating(function () {
            $this->app->get(Deprecator::class)->storeLogs();
        });
    }

    public function boot(): void
    {
        $this->commands([
            ClearDeprecations::class,
        ]);
    }
}
