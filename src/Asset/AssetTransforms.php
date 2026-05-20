<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;
use CraftCms\Cms\Image\Contracts\EagerImageTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Events\AssetTransformersResolving;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

#[Singleton]
class AssetTransforms
{
    /** @var array<string, AssetTransformerInterface> */
    private array $assetTransformers = [];

    /**
     * Eager-loads transform indexes for the given list of assets.
     *
     * You can include `srcset`-style sizes (e.g. `100w` or `2x`) following a normal transform definition, for example:
     *
     * ```php
     * [['width' => 1000, 'height' => 600], '1.5x', '2x', '3x']
     * ```
     *
     * When a `srcset`-style size is encountered, the preceding normal transform definition will be used as a
     * reference when determining the resulting transform dimensions.
     *
     * @param  array  $transforms  The transform definitions to eager-load
     * @param  Asset[]  $assets  The assets to eager-load transforms for
     */
    public function eagerLoadTransforms(array $assets, array $transforms): void
    {
        if (empty($assets) || empty($transforms)) {
            return;
        }

        $normalizedTransforms = [];

        /** @var ImageTransform|null $refTransform */
        $refTransform = null;

        foreach ($transforms as $transform) {
            try {
                [$sizeValue, $sizeUnit] = AssetsHelper::parseSrcsetSize($transform);
            } catch (InvalidArgumentException) {
                $sizeValue = $sizeUnit = null;
            }

            if (isset($sizeValue, $sizeUnit)) {
                if ($refTransform === null || ! $refTransform->width) {
                    throw new InvalidArgumentException("Can’t eager-load transform “{$transform}” without a prior transform that specifies the base width");
                }

                $transform = new ImageTransform(
                    $refTransform->toArray(),
                );

                unset($transform->name, $transform->handle);

                if ($sizeUnit === 'w') {
                    $transform->width = (int) $sizeValue;
                } else {
                    $transform->width = (int) ceil($refTransform->width * $sizeValue);
                }

                if ($refTransform->height) {
                    if ($sizeUnit === 'w') {
                        $transform->height = (int) ceil($refTransform->height * $transform->width / $refTransform->width);
                    } else {
                        $transform->height = (int) ceil($refTransform->height * $sizeValue);
                    }
                }
            }

            $transform = ImageTransformHelper::normalizeTransform($transform);
            if ($transform === null) {
                continue;
            }

            $normalizedTransforms[] = $transform;

            if (! isset($sizeValue)) {
                $refTransform = $transform;
            }
        }

        $transformsByTransformer = [];

        foreach ($normalizedTransforms as $transformKey => $transform) {
            $transformerHandle = $transform->getTransformer();

            if ($transformerHandle !== null) {
                $transformerHandle = $this->resolveTransformerHandle($transformerHandle);
                $groupTransform = clone $transform;
                $groupTransform->setTransformer($transformerHandle);
                $transformsByTransformer[$transformerHandle]['transforms'][$transformKey] = $groupTransform;
                $transformsByTransformer[$transformerHandle]['assets'] = $assets;

                continue;
            }

            foreach ($assets as $assetKey => $asset) {
                $transformerHandle = $this->resolveTransformerHandle($asset->getVolume()->getFs()->getDefaultTransformer());

                if (! isset($transformsByTransformer[$transformerHandle]['transforms'][$transformKey])) {
                    $groupTransform = clone $transform;
                    $groupTransform->setTransformer($transformerHandle);
                    $transformsByTransformer[$transformerHandle]['transforms'][$transformKey] = $groupTransform;
                }

                $transformsByTransformer[$transformerHandle]['assets'][$assetKey] = $asset;
            }
        }

        foreach ($transformsByTransformer as $type => $group) {
            $transformer = $this->getAssetTransformer($type);

            if ($transformer instanceof EagerImageTransformerInterface) {
                $transformer->eagerLoadTransforms(
                    array_values($group['transforms']),
                    array_values($group['assets']),
                );
            }
        }
    }

    public function getAssetTransformer(?string $handle = null): AssetTransformerInterface
    {
        $handle = $this->resolveTransformerHandle($handle);

        if (array_key_exists($handle, $this->assetTransformers)) {
            return $this->assetTransformers[$handle];
        }

        $types = $this->getAllAssetTransformers();

        if (array_key_exists($handle, $types)) {
            $type = $types[$handle];

            return $this->assetTransformers[$handle] = is_string($type)
                ? app()->make($type)
                : $type;
        }

        if (is_a($handle, AssetTransformerInterface::class, true)) {
            return $this->assetTransformers[$handle] = app()->make($handle);
        }

        Log::warning("Invalid asset transformer: $handle. Falling back to craft.", [__METHOD__]);

        return $this->assetTransformers[ImageTransform::DEFAULT_TRANSFORMER]
            ??= app()->make(ImageTransformer::class);
    }

    public function resolveTransformerHandle(?string $handle): string
    {
        $handle = Env::parse($handle);

        if ($handle === null || $handle === '' || $handle === ImageTransformer::class) {
            return ImageTransform::DEFAULT_TRANSFORMER;
        }

        $types = $this->getAllAssetTransformers();
        if (array_key_exists($handle, $types)) {
            return $handle;
        }

        if (is_a($handle, AssetTransformerInterface::class, true)) {
            $transformerHandle = $handle::handle();

            return $transformerHandle !== '' ? $transformerHandle : $handle;
        }

        return $handle;
    }

    /**
     * @return array<string, class-string<AssetTransformerInterface>|AssetTransformerInterface>
     */
    public function getAllAssetTransformers(): array
    {
        $transformers = [
            ImageTransform::DEFAULT_TRANSFORMER => ImageTransformer::class,
        ];

        event($event = new AssetTransformersResolving(types: $transformers));

        foreach ($event->types as $handle => $class) {
            if (
                ! is_string($handle) ||
                $handle === '' ||
                (! is_string($class) && ! $class instanceof AssetTransformerInterface) ||
                (is_string($class) && ! is_subclass_of($class, AssetTransformerInterface::class))
            ) {
                $type = is_object($class) ? $class::class : (string) $class;

                throw new ImageTransformException("Invalid asset transformer: $type");
            }
        }

        return $event->types;
    }

    /**
     * Deletes ALL transform data (including thumbs and sources) associated with the asset.
     */
    public function deleteAllTransformData(Asset $asset): void
    {
        $this->deleteResizedAssetVersion($asset);
        $this->deleteCreatedTransformsForAsset($asset);

        $file = Path::assetSources($asset->id.'.'.pathinfo($asset->getFilename(), PATHINFO_EXTENSION));

        File::delete($file);
    }

    public function deleteResizedAssetVersion(Asset $asset): void
    {
        $dirs = [
            Path::imageEditorSources((string) $asset->id),
        ];

        foreach ($dirs as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir.'/[0-9]*/'.$asset->id.'.[a-z]*');

                if (! is_array($files)) {
                    Log::info('Could not list files in '.$dir.' when deleting resized asset versions.');

                    continue;
                }

                foreach ($files as $path) {
                    if (! File::delete($path)) {
                        Log::warning("Unable to delete the asset thumbnail \"$path\".", [__METHOD__]);
                    }
                }
            }
        }
    }

    public function deleteCreatedTransformsForAsset(Asset $asset): void
    {
        event(new AssetTransformsInvalidating(asset: $asset));

        foreach ($this->getAllAssetTransformers() as $handle => $type) {
            $transformer = $this->getAssetTransformer($handle);
            $transformer->invalidateAssetTransforms($asset);
        }
    }

    public function reset(): void
    {
        $this->assetTransformers = [];
    }
}
