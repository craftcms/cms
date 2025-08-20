<?php

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Plugin\Commands\DisableCommand;
use CraftCms\Cms\Plugin\Commands\EnableCommand;
use CraftCms\Cms\Plugin\Commands\InstallCommand;
use CraftCms\Cms\Plugin\Commands\ListCommand;
use CraftCms\Cms\Plugin\Commands\UninstallCommand;
use Illuminate\Support\ServiceProvider;

final class PluginsServiceProvider extends ServiceProvider
{
    public function boot(Plugins $plugins): void
    {
        $plugins->loadPlugins();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ListCommand::class,
            InstallCommand::class,
            UninstallCommand::class,
            EnableCommand::class,
            DisableCommand::class,
        ]);
    }
}
