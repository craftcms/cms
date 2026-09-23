<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::subscribe(WorkflowContentChanges::class);
    }
}
