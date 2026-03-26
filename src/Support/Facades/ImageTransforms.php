<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getAllTransforms()
 * @method static \CraftCms\Cms\Image\Data\ImageTransform|null getTransformByHandle(string $handle)
 * @method static \CraftCms\Cms\Image\Data\ImageTransform|null getTransformById(int $id)
 * @method static \CraftCms\Cms\Image\Data\ImageTransform|null getTransformByUid(string $uid)
 * @method static bool saveTransform(\CraftCms\Cms\Image\Data\ImageTransform $transform, bool $runValidation = true)
 * @method static void handleChangedTransform(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteTransformById(int $id)
 * @method static bool deleteTransform(\CraftCms\Cms\Image\Data\ImageTransform $transform)
 * @method static void handleDeletedTransform(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void eagerLoadTransforms(\CraftCms\Cms\Asset\Elements\Asset[] $assets, array $transforms)
 * @method static mixed getImageTransformer(string $class, array $config = [])
 * @method static string[] getAllImageTransformers()
 * @method static void deleteAllTransformData(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static void deleteResizedAssetVersion(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static void deleteCreatedTransformsForAsset(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static void reset()
 *
 * @see \CraftCms\Cms\Image\ImageTransforms
 */
class ImageTransforms extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Image\ImageTransforms::class;
    }
}
