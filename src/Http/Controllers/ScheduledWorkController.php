<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Console\ScheduledWork;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

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
        if ($this->scheduledWork->claimRun()) {
            defer(fn () => $this->scheduledWork->runDue())->always();
        }

        return response()->make();
    }
}
