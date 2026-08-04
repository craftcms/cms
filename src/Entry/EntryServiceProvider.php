<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry;

use CraftCms\Cms\Entry\Commands\MergeEntryTypesCommand;
use CraftCms\Cms\Entry\Commands\UpdateStatusesCommand;
use Illuminate\Support\ServiceProvider;

class EntryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            MergeEntryTypesCommand::class,
            UpdateStatusesCommand::class,
        ]);
    }
}
