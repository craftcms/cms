<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand;
use CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand;
use CraftCms\Cms\Asset\Commands\IndexOneAssetCommand;
use Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            IndexAllAssetsCommand::class,
            IndexOneAssetCommand::class,
            CleanupAssetIndexesCommand::class,
        ]);
    }
}
