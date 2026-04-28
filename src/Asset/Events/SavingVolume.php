<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event SavingVolume The event that is triggered before a volume is saved.
 */
class SavingVolume
{
    public function __construct(
        public Volume $volume,
        public bool $isNew = false,
    ) {}
}
