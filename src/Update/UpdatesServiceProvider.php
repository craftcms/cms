<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update;

use CraftCms\Cms\Update\Commands\ComposerInstallCommand;
use CraftCms\Cms\Update\Commands\InfoCommand;
use CraftCms\Cms\Update\Commands\UpdateCommand;
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
