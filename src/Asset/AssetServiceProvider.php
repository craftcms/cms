<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand;
use CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand;
use CraftCms\Cms\Asset\Commands\IndexOneAssetCommand;
use CraftCms\Cms\Asset\Events\AssetTransformerDeleting;
use CraftCms\Cms\Asset\Events\AssetTransformerUpdating;
use CraftCms\Cms\Filesystem\Events\FilesystemRenamed;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider
{
    public function boot(ImageTransformer $imageTransformer, AssetTransformers $assetTransformers): void
    {
        Event::listen(
            AssetTransformsInvalidating::class,
            $imageTransformer->handleAssetTransformsInvalidating(...),
        );
        Event::listen(
            AssetTransformerUpdating::class,
            $imageTransformer->handleAssetTransformerUpdating(...),
        );
        Event::listen(
            AssetTransformerDeleting::class,
            $imageTransformer->handleAssetTransformerDeleting(...),
        );
        Event::listen(
            FilesystemRenamed::class,
            $assetTransformers->handleFilesystemRenamed(...),
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
