<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand;
use CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand;
use CraftCms\Cms\Asset\Commands\IndexOneAssetCommand;
use CraftCms\Cms\Asset\Events\AssetProcessorDeleting;
use CraftCms\Cms\Asset\Events\AssetProcessorUpdating;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider
{
    public function boot(ImageTransformer $imageTransformer, AssetProcessors $assetProcessors): void
    {
        Event::listen(
            AssetTransformsInvalidating::class,
            $imageTransformer->handleAssetTransformsInvalidating(...),
        );
        Event::listen(
            AssetProcessorUpdating::class,
            $imageTransformer->handleAssetProcessorUpdating(...),
        );
        Event::listen(
            AssetProcessorDeleting::class,
            $imageTransformer->handleAssetProcessorDeleting(...),
        );
        Event::listen(
            FilesystemRenamed::class,
            $assetProcessors->handleFilesystemRenamed(...),
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
