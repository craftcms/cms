<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;

/**
 * @event FolderCreated The event that is triggered after a folder is created.
 */
class FolderCreated
{
    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
