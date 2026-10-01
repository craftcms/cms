<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;

/**
 * @event AssetFileHandling The event that is triggered before an asset is uploaded to volume.
 *
 * @since 6.0.0
 */
class AssetFileHandling
{
    public function __construct(
        public Asset $asset,
        public bool $isNew,
    ) {}
}
