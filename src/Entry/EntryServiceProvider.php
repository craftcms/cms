<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry;

use CraftCms\Cms\Entry\Commands\ImportEntriesCommand;
use CraftCms\Cms\Entry\Commands\MergeEntryTypesCommand;
use CraftCms\Cms\Entry\Commands\UpdateStatusesCommand;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class EntryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            ImportEntriesCommand::class,
            MergeEntryTypesCommand::class,
            UpdateStatusesCommand::class,
        ]);
    }
}
