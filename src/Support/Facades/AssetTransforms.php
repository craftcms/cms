<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void eagerLoadTransforms(array $assets, array $transforms)
 * @method static \CraftCms\Cms\Image\Contracts\AssetTransformerInterface getAssetTransformer(?string $handle = null)
 * @method static string resolveTransformerHandle(?string $handle)
 * @method static array<string, class-string<\CraftCms\Cms\Image\Contracts\AssetTransformerInterface>|\CraftCms\Cms\Image\Contracts\AssetTransformerInterface> getAllAssetTransformers()
 * @method static void deleteAllTransformData(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static void deleteResizedAssetVersion(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static void deleteCreatedTransformsForAsset(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static void reset()
 *
 * @see \CraftCms\Cms\Asset\AssetTransforms
 */
class AssetTransforms extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Asset\AssetTransforms::class;
    }
}
