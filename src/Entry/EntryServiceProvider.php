<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry;

use CraftCms\Cms\Entry\Commands\MergeEntryTypes;
use Illuminate\Support\ServiceProvider;

final class EntryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            MergeEntryTypes::class,
        ]);
    }
}
