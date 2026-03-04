<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static Collection<int, ImageTransform> getAllTransforms()
 * @method static ImageTransform|null getTransformByHandle(string $handle)
 * @method static ImageTransform|null getTransformById(int $id)
 * @method static ImageTransform|null getTransformByUid(string $uid)
 * @method static bool saveTransform(ImageTransform $transform, bool $runValidation = true)
 * @method static void handleChangedTransform(ConfigEvent $event)
 * @method static bool deleteTransformById(int $id)
 * @method static bool deleteTransform(ImageTransform $transform)
 * @method static void handleDeletedTransform(ConfigEvent $event)
 * @method static void eagerLoadTransforms(array $assets, array $transforms)
 * @method static ImageTransformerInterface getImageTransformer(string $class, array $config = [])
 * @method static array getAllImageTransformers()
 * @method static void deleteAllTransformData(Asset $asset)
 * @method static void deleteResizedAssetVersion(Asset $asset)
 * @method static void deleteCreatedTransformsForAsset(Asset $asset)
 *
 * @see \CraftCms\Cms\Image\ImageTransforms
 */
final class ImageTransforms extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Image\ImageTransforms::class;
    }
}
