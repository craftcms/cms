<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Folders;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * @event FolderCreated The event that is triggered after a folder is created.
 *
 * Folders that indexing, uploads, and other code create with
 * {@see Folders::ensureFolderByFullPathAndVolume()} don’t trigger it.
 *
 * @since 6.0.0
 */
class FolderCreated implements ShouldDispatchAfterCommit
{
    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
