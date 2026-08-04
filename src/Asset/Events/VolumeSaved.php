<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event VolumeSaved The event that is triggered after a volume is saved.
 */
class VolumeSaved
{
    public function __construct(
        public Volume $volume,
        public bool $isNew = false,
    ) {}
}
