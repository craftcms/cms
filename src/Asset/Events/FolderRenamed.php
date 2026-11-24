<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * @event FolderRenamed The event that is triggered after a folder is renamed.
 *
 * @since 6.0.0
 */
class FolderRenamed implements ShouldDispatchAfterCommit
{
    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
