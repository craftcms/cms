<?php

declare(strict_types=1);

namespace CraftCms\Cms\EntryType;

use CraftCms\Cms\EntryType\Commands\MergeCommand;
use Illuminate\Support\ServiceProvider;

final class EntryTypeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            MergeCommand::class,
        ]);
    }
}
