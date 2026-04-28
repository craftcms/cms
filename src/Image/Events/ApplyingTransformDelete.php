<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Image\Data\ImageTransform;

class ApplyingTransformDelete
{
    public function __construct(
        public ImageTransform $transform,
    ) {}
}
