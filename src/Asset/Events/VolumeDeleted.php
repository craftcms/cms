<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event VolumeDeleted The event that is triggered after a volume is deleted.
 */
final class VolumeDeleted
{
    public function __construct(
        public Volume $volume,
    ) {}
}
