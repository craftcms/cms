<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event ApplyingVolumeDelete The event that is triggered before a volume delete is applied to the database.
 */
final class ApplyingVolumeDelete
{
    public function __construct(
        public Volume $volume,
    ) {}
}
