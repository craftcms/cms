<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator;

use CraftCms\Cms\Deprecator\Commands\ClearDeprecationsCommand;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class DeprecatorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Resolved when the request ends, as the deprecator is request-scoped, and the app can outlive a request (such as under Laravel Octane).
        $this->app->terminating(fn () => $this->app->make(Deprecator::class)->storeLogs());

        $this->commands([
            ClearDeprecationsCommand::class,
        ]);
    }
}
