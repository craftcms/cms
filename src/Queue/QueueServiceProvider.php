<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Listeners\StoreCompleted;
use CraftCms\Cms\Queue\Listeners\StoreFailed;
use CraftCms\Cms\Queue\Listeners\StoreJob;
use CraftCms\Cms\Queue\Listeners\StoreReserved;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->ensureRetryAfterExceedsJobTimeout();
    }

    private function ensureRetryAfterExceedsJobTimeout(): void
    {
        /** @var array<string, array<string, mixed>> $connections */
        $connections = config('queue.connections');

        foreach ($connections as $name => $connection) {
            if (! array_key_exists('retry_after', $connection)) {
                continue;
            }

            $connections[$name]['retry_after'] = max(360, (int) $connection['retry_after']);
        }

        config()->set('queue.connections', $connections);
    }

    public function boot(): void
    {
        Event::listen(JobQueued::class, StoreJob::class);
        Event::listen(JobProcessing::class, StoreReserved::class);
        Event::listen(JobProcessed::class, StoreCompleted::class);
        Event::listen(JobFailed::class, StoreFailed::class);
    }
}
