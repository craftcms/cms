<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Exceptions\AssetOperationException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event FolderCreating The event that is triggered before a folder is created.
 *
 * Set {@see $isValid} to `false` to prevent the folder from being created.
 * {@see Folders::createFolder()} then throws an {@see AssetOperationException}.
 *
 * Only {@see Folders::createFolder()} triggers it. Folders that indexing, uploads, folder moves, and other code
 * create can’t be prevented, but they still trigger {@see FolderCreated}.
 *
 * @since 6.0.0
 */
class FolderCreating
{
    use ValidatableEvent;

    public function __construct(
        public VolumeFolder $folder,
    ) {}
}
