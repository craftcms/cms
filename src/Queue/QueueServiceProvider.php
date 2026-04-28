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
    public function boot(): void
    {
        Event::listen(JobQueued::class, StoreJob::class);
        Event::listen(JobProcessing::class, StoreReserved::class);
        Event::listen(JobProcessed::class, StoreCompleted::class);
        Event::listen(JobFailed::class, StoreFailed::class);
    }
}
