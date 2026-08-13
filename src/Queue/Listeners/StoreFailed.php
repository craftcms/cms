<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobFailed;

readonly class StoreFailed extends ProgressListener
{
    public function handle(JobFailed $event): void
    {
        $queue = $event->job->getQueue();

        if (! $this->shouldTrackQueue($queue)) {
            return;
        }

        $uuid = $this->jobUuid($event->job->payload());

        if ($uuid === null || ($queue === 'sync' && ! $this->progress->exists($uuid))) {
            return;
        }

        $this->progress->failed(
            uid: $uuid,
            error: $event->exception->getMessage(),
        );
    }
}
