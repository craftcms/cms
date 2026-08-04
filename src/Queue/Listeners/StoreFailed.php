<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Listeners;

use Illuminate\Queue\Events\JobFailed;

readonly class StoreFailed extends ProgressListener
{
    public function handle(JobFailed $event): void
    {
        if (! $this->shouldTrackQueue($event->job->getQueue())) {
            return;
        }

        $uuid = $this->jobUuid($event->job->payload());

        if ($uuid === null) {
            return;
        }

        $this->progress->failed(
            uid: $uuid,
            error: $event->exception->getMessage(),
        );
    }
}
