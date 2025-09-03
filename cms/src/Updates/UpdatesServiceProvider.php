<?php

namespace CraftCms\Cms\Updates;

use CraftCms\Cms\Updates\Commands\ComposerInstallCommand;
use CraftCms\Cms\Updates\Commands\InfoCommand;
use CraftCms\Cms\Updates\Commands\UpdateCommand;
use Illuminate\Support\ServiceProvider;

final class UpdatesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            ComposerInstallCommand::class,
            UpdateCommand::class,
            InfoCommand::class,
        ]);
    }
}
