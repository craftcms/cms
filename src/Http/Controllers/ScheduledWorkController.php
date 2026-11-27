<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\ScheduledWork;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs Craft’s due scheduled tasks after the response, for site requests that couldn’t run them after their own.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ScheduledWorkController
{
    public function __construct(
        private ScheduledWork $scheduledWork,
    ) {}

    public function run(): Response
    {
        if (
            Cms::config()->runScheduledWorkAutomatically &&
            ! app()->isDownForMaintenance() &&
            $this->scheduledWork->claimRequestRun()
        ) {
            app()->terminating(fn () => $this->scheduledWork->runDue());
        }

        return response()->make();
    }
}
