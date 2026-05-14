<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Contracts;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @deprecated 6.0.0 use AssetTransformerInterface instead.
 */
interface ImageTransformerInterface
{
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string;

    public function invalidateAssetTransforms(Asset $asset): void;
}
