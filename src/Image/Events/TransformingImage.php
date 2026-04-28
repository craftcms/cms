<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;

class TransformingImage
{
    public ?string $tempPath = null;

    public function __construct(
        public Asset $asset,
        public ImageTransformIndex $imageTransformIndex,
        public ImageTransform $transform,
    ) {}
}
