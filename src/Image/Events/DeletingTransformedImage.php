<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransformIndex;

class DeletingTransformedImage
{
    public function __construct(
        public Asset $asset,
        public ImageTransformIndex $imageTransformIndex,
        public string $path,
    ) {}
}
