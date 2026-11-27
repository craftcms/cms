<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransformIndex;

/**
 * The event that is triggered before the `craft` transformer deletes a transform file, such as when its asset changes
 * or its transform’s settings do.
 *
 * Caches can use {@see urls()} to purge the URLs the file was served from. It’s dispatched before the file is deleted,
 * and before any surrounding transaction commits, so purge listeners should defer the purge, such as to the end of the
 * request or a queue job.
 *
 * @since 6.0.0
 */
class DeletingTransformedImage
{
    public function __construct(
        public Asset $asset,
        public ImageTransformIndex $imageTransformIndex,
        /** The file’s path on {@see $disk} */
        public string $path,
        /** The name of the disk the file is stored on */
        public ?string $disk = null,
        /** The URL the file was served from, or null if it wasn’t generated or transforms don’t have public URLs */
        public ?string $url = null,
    ) {}

    /**
     * Returns the URLs the file was served from: the URL as it was served, and the same URL without its query string.
     *
     * The served URL’s `v` revision parameter is rebuilt from when the transform was generated. Older `v` values, from
     * transforms generated and deleted before, can’t be recovered; URLs with them expire on the CDN’s own TTL.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        if ($this->url === null) {
            return [];
        }

        return array_values(array_unique([$this->url, strtok($this->url, '?')]));
    }
}
