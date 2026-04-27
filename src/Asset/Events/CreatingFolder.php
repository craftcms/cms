<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event CreatingFolder The event that is triggered before a folder is created.
 */
class CreatingFolder
{
    use ValidatableEvent;

    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
