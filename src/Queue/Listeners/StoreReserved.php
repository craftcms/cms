<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobProcessing;

final readonly class StoreReserved extends ProgressListener
{
    public function handle(JobProcessing $event): void
    {
        if (! $this->shouldTrackQueue($event->job->getQueue())) {
            return;
        }

        $uuid = $this->jobUuid($event->job->payload());

        if ($uuid === null) {
            return;
        }

        $this->progress->processing($uuid, $this->jobDescription($event->job));
    }
}
