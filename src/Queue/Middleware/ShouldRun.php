<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Middleware;

use Closure;
use CraftCms\Cms\Queue\Job;

/**
 * Laravel queue middleware that checks if a job should still run.
 *
 * If the job's progress entry has been deleted (canceled), the job
 * will be deleted from the queue without executing.
 */
class ShouldRun
{
    public function handle(object $job, Closure $next): void
    {
        if (! $job instanceof Job) {
            $next($job);

            return;
        }

        if (! $job->shouldStillRun()) {
            $job->job?->delete();

            return;
        }

        $next($job);
    }
}
