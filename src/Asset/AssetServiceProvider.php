<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand;
use CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand;
use CraftCms\Cms\Asset\Commands\IndexOneAssetCommand;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider
{
    public function boot(ImageTransformer $imageTransformer): void
    {
        Event::listen(
            AssetTransformsInvalidating::class,
            $imageTransformer->handleAssetTransformsInvalidating(...),
        );

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
