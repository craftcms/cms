<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Plugin\Commands\DisableCommand;
use CraftCms\Cms\Plugin\Commands\EnableCommand;
use CraftCms\Cms\Plugin\Commands\InstallCommand;
use CraftCms\Cms\Plugin\Commands\ListCommand;
use CraftCms\Cms\Plugin\Commands\UninstallCommand;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->booted(fn () => $this->app->make(Plugins::class)->loadPlugins());
    }

    public function boot(): void
    {
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
