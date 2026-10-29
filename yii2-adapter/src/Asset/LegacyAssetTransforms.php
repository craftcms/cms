<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use BadMethodCallException;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use Illuminate\Container\Attributes\Scoped;
use WeakMap;

/**
 * Transforms set on native assets with the legacy `setTransform()` method.
 *
 * They only apply to the asset's URL, not its width, height or `<img>` tag.
 */
#[Scoped]
class LegacyAssetTransforms
{
    /** @var WeakMap<Asset, ImageTransform> */
    private WeakMap $transforms;

    public function __construct()
    {
        /** @var WeakMap<Asset, ImageTransform> $transforms */
        $transforms = new WeakMap();
        $this->transforms = $transforms;
    }

    /**
     * Stores a transform for the asset, like Craft 5's `Asset::setTransform()`.
     */
    public function setTransform(object $asset, mixed $transform): Asset
    {
        if (!$asset instanceof Asset) {
            throw new BadMethodCallException(sprintf('Method %s::setTransform does not exist.', $asset::class));
        }

        $allowed = match ($asset->getMimeType()) {
            'image/gif' => Cms::config()->transformGifs,
            'image/svg+xml' => Cms::config()->transformSvgs,
            default => true,
        };

        if (!$allowed) {
            return $asset;
        }

        $transform = ImageTransformHelper::normalizeTransform($transform);

        if ($transform === null) {
            unset($this->transforms[$asset]);
        } else {
            $this->transforms[$asset] = $transform;
        }

        return $asset;
    }

    public function handleUrlResolving(AssetUrlResolving $event): void
    {
        $transform = $this->transforms[$event->asset] ?? null;

        if ($transform === null || $event->transform !== null || $event->url !== null || $event->handled) {
            return;
        }

        try {
            $event->url = $event->asset->transform($transform)->url;
        } catch (AssetTransformException|NotSupportedException|FilesystemException $exception) {
            report($exception);
        }

        $event->handled = true;
    }
}
