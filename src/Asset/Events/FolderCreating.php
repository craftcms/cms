<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event FolderCreating The event that is triggered before a folder is created.
 */
class FolderCreating
{
    use ValidatableEvent;

    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
