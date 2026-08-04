<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event VolumeSaving The event that is triggered before a volume is saved.
 */
class VolumeSaving
{
    public function __construct(
        public Volume $volume,
        public bool $isNew = false,
    ) {}
}
