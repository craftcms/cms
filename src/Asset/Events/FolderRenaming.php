<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Exceptions\AssetOperationException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event FolderRenaming The event that is triggered before a folder is renamed.
 *
 * {@see $newName} is the name after Craft cleans it. Set {@see $isValid} to `false` to prevent
 * the rename. {@see Folders::renameFolderById()} then throws an {@see AssetOperationException}.
 *
 * @since 6.0.0
 */
class FolderRenaming
{
    use ValidatableEvent;

    public function __construct(
        public VolumeFolder $folder,
        public string $newName,
    ) {}
}
