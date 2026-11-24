<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after a folder is renamed, along with its subfolders and files.
 *
 * Asset events don’t fire for the files that moved with the folder. Caches can use {@see prefixes()} to purge
 * everything under the old folder’s URL.
 *
 * @since 6.0.0
 */
class FolderRenamed implements ShouldDispatchAfterCommit
{
    public function __construct(
        /** The folder, with its new name and path */
        public VolumeFolder $folder,
        /** The folder’s path before the rename, with a trailing slash */
        public ?string $oldPath = null,
    ) {}

    /**
     * Returns the URL prefixes the folder’s files were served from before the rename.
     *
     * @return list<string>
     */
    public function prefixes(): array
    {
        $volume = $this->folder->getVolume();

        if ($this->oldPath === null || ! $volume->sourceHasUrls()) {
            return [];
        }

        $path = implode('/', array_map(rawurlencode(...), explode('/', $this->oldPath)));

        return [$volume->sourceDisk()->url($path)];
    }
}
