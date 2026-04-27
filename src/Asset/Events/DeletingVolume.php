<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event DeletingVolume The event that is triggered before a volume is deleted.
 */
class DeletingVolume
{
    public function __construct(
        public Volume $volume,
    ) {}
}
