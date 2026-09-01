<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Image;

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
