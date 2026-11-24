<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Folders;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * @event FolderCreated The event that is triggered after a folder record is created.
 *
 * It fires for every new folder, including folders that indexing and uploads create, and a volume’s root folder.
 * Folders for temporary uploads have no volume, so their {@see VolumeFolder::$volumeId} is `null`.
 *
 * @since 6.0.0
 */
class FolderCreated implements ShouldDispatchAfterCommit
{
    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
