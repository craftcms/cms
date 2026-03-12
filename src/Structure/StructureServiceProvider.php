<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure;

use CraftCms\Cms\Structure\Commands\RepairSectionStructureCommand;
use Illuminate\Support\ServiceProvider;

class StructureServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            RepairSectionStructureCommand::class,
        ]);
    }
}
