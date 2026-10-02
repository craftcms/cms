<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\AssetTransformer;

/**
 * @since 6.0.0
 */
readonly class AssetTransformerUpdating
{
    public function __construct(
        public AssetTransformer $oldTransformer,
        public AssetTransformer $newTransformer,
    ) {}
}
