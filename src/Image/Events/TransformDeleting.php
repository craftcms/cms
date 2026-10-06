<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @since 6.0.0
 */
class TransformDeleting
{
    public function __construct(
        public ImageTransform $transform,
    ) {}
}
