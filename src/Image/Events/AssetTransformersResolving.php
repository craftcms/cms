<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;

class AssetTransformersResolving
{
    /**
     * @param  array<string, class-string<AssetTransformerInterface>|AssetTransformerInterface>  $types
     */
    public function __construct(
        public array $types = [],
    ) {}
}
