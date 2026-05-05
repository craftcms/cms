<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event AssetIndexVolumesResolving The event that is triggered when listing the available volumes to index.
 */
class AssetIndexVolumesResolving
{
    public function __construct(
        /** @var Volume[] The volumes to be listed. */
        public array $volumes
    ) {}
}
