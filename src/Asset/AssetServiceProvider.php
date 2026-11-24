<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand;
use CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand;
use CraftCms\Cms\Asset\Commands\IndexOneAssetCommand;
use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Asset\Events\TransformGenerated;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Support\Facades\Deprecator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class AssetServiceProvider extends ServiceProvider
{
    public function boot(ImageTransformer $imageTransformer): void
    {
        Event::listen(
            AssetTransformsInvalidating::class,
            $imageTransformer->handleAssetTransformsInvalidating(...),
        );
        Event::listen(function (TransformGenerated $event) {
            if (Event::hasListeners(AfterGenerateTransform::class)) {
                Deprecator::log(AfterGenerateTransform::class, '`'.AfterGenerateTransform::class.'` is deprecated. Listen to `'.TransformGenerated::class.'` instead.');
                event(new AfterGenerateTransform($event->asset, $event->transform, $event->url));
            }
        });
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
