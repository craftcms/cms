<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

/**
 * @event VolumeSaved The event that is triggered after a volume is saved.
 *
 * Compare {@see $volume} with {@see $oldVolume} to see whether settings that affect asset URLs changed, such as
 * its filesystem, subpath or `hasUrls`.
 *
 * @since 6.0.0
 */
class VolumeSaved
{
    public function __construct(
        public Volume $volume,
        public bool $isNew = false,
        /** The volume as it was before the save, or `null` for new volumes */
        public ?Volume $oldVolume = null,
    ) {}
}
