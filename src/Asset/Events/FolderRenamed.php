<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;

/**
 * @event FolderRenamed The event that is triggered after a folder is renamed.
 *
 * @since 6.0.0
 */
class FolderRenamed
{
    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
