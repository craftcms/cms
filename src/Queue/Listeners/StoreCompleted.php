<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobProcessed;

readonly class StoreCompleted extends ProgressListener
{
    public function handle(JobProcessed $event): void
    {
        if (! $this->shouldTrackQueue($event->job->getQueue())) {
            return;
        }

        $uuid = $this->jobUuid($event->job->payload());

        if ($uuid === null) {
            return;
        }

        $this->progress->completed($uuid);
    }
}
