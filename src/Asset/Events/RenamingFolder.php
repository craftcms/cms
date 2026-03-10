<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event RenamingFolder The event that is triggered before a folder is renamed.
 */
final class RenamingFolder
{
    use ValidatableEvent;

    public function __construct(
        public VolumeFolder $folder,
        public string $newName,
    ) {}
}
