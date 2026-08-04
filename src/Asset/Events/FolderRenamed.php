<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;

/**
 * @event FolderRenamed The event that is triggered after a folder is renamed.
 */
class FolderRenamed
{
    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
