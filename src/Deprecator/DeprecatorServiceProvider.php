<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator;

use CraftCms\Cms\Deprecator\Commands\ClearDeprecationsCommand;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Event;
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

        // A queue worker only terminates when it stops, and it forgets scoped instances before each job.
        Event::listen([JobProcessed::class, JobExceptionOccurred::class], function (JobProcessed|JobExceptionOccurred $event): void {
            if (! $event->job instanceof SyncJob) {
                $this->app->make(Deprecator::class)->storeLogs();
            }
        });

        $this->commands([
            ClearDeprecationsCommand::class,
        ]);
    }
}
