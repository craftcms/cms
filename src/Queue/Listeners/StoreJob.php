<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobRetryRequested;

readonly class StoreJob extends ProgressListener
{
    public function handle(JobQueued|JobRetryRequested $event): void
    {
        $retry = $event instanceof JobRetryRequested;

        if (! $this->shouldTrackQueue($retry ? $event->job->queue : $event->queue)) {
            return;
        }

        $uuid = $this->jobUuid($event->payload());

        if ($uuid === null) {
            return;
        }

        $this->progress->queued(
            $uuid,
            $retry ? ($event->payload()['displayName'] ?? 'Unknown job') : $this->jobDescription($event->job),
            $retry ? null : $event->delay,
        );
    }
}
