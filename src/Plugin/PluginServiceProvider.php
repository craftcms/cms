<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Plugin\Commands\DisableCommand;
use CraftCms\Cms\Plugin\Commands\EnableCommand;
use CraftCms\Cms\Plugin\Commands\InstallCommand;
use CraftCms\Cms\Plugin\Commands\ListCommand;
use CraftCms\Cms\Plugin\Commands\UninstallCommand;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        /**
         * Override the default Laravel package manifest so we
         * can automatically detect and register Plugins.
         */
        $this->app->instance(
            PackageManifest::class,
            $this->app->make(PluginPackageManifest::class, [
                'basePath' => $this->app->basePath(),
                'manifestPath' => $this->app->getCachedPackagesPath(),
            ]),
        );
    }

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
