<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates;

use CraftCms\Cms\Updates\Commands\ComposerInstallCommand;
use CraftCms\Cms\Updates\Commands\InfoCommand;
use CraftCms\Cms\Updates\Commands\UpdateCommand;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
class UpdatesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            ComposerInstallCommand::class,
            UpdateCommand::class,
            InfoCommand::class,
        ]);
    }
}
