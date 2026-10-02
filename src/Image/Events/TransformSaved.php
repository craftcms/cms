<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @since 6.0.0
 */
class TransformSaved
{
    public function __construct(
        public ImageTransform $transform,
        public bool $isNew = false,
    ) {}
}
