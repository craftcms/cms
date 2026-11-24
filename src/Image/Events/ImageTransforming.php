<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Asset\Events\TransformGenerating;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Image;

/**
 * @event ImageTransforming The event that is triggered when the `craft` asset transform driver has generated a
 * transformed image, before it writes the file to the transform disk.
 *
 * Set {@see $tempPath} to write a different file instead. Other transform drivers don’t trigger it.
 * {@see TransformGenerating} and {@see AfterGenerateTransform} fire for every driver.
 *
 * @since 6.0.0
 */
class ImageTransforming
{
    public function __construct(
        public Asset $asset,
        public ImageTransformIndex $imageTransformIndex,
        public ImageTransform $transform,
        public string $path,
        public ?Image $image = null,
        public ?string $tempPath = null,
    ) {}
}
