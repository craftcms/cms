<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobProcessed;

readonly class StoreCompleted extends ProgressListener
{
    public function handle(JobProcessed $event): void
    {
        $queue = $event->job->getQueue();

        if (! $this->shouldTrackQueue($queue)) {
            return;
        }

        $uuid = $this->jobUuid($event->job->payload());

        if ($uuid === null || ($queue === 'sync' && ! $this->progress->exists($uuid))) {
            return;
        }

        $this->progress->completed($uuid);
    }
}
