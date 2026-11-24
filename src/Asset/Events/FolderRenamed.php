<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after a folder is renamed, along with its subfolders and files.
 *
 * Asset events don’t fire for the files that moved with the folder, or for their transforms, which move along with
 * it. Caches can use {@see prefixes()} to purge everything under the old folder’s URL and its transforms’ URL.
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
     * Returns the URL prefixes the folder’s files and their transforms were served from before the rename.
     *
     * @return list<string>
     */
    public function prefixes(): array
    {
        if ($this->oldPath === null) {
            return [];
        }

        $volume = $this->folder->getVolume();
        $prefixes = [];

        if ($volume->sourceHasUrls()) {
            $path = implode('/', array_map(rawurlencode(...), explode('/', $this->oldPath)));
            $prefixes[] = Html::encodeSpaces($volume->sourceDisk()->url($path));
        }

        $transformPrefix = app(ImageTransformer::class)->getTransformFolderUrl($volume, $this->oldPath);
        if ($transformPrefix !== null) {
            $prefixes[] = $transformPrefix;
        }

        return $prefixes;
    }
}
