<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event VolumeDeleting The event that is triggered before a volume is deleted.
 *
 * @since 6.0.0
 */
class VolumeDeleting
{
    public function __construct(
        public Volume $volume,
    ) {}
}
