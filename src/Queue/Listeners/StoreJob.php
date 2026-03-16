<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobQueued;

readonly class StoreJob extends ProgressListener
{
    public function handle(JobQueued $event): void
    {
        if (! $this->shouldTrackQueue($event->queue)) {
            return;
        }

        $uuid = $this->jobUuid($event->payload());

        if ($uuid === null) {
            return;
        }

        $this->progress->queued($uuid, $this->jobDescription($event->job), $event->delay);
    }
}
