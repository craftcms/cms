<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

class ImageTransformersResolving
{
    /**
     * @param  class-string[]  $types
     */
    public function __construct(
        public array $types = [],
    ) {}
}
