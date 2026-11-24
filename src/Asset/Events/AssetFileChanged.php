<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetFileChange;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after an existing asset’s file is replaced, moved or deleted, once the change is committed.
 *
 * It doesn’t fire for new assets, or when an asset is deleted with `keepFileOnDelete` set. Caches can use
 * {@see urls()} to purge the URL the file was served from.
 *
 * @since 6.0.0
 */
class AssetFileChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        /** The asset, as it is after the change */
        public Asset $asset,
        public AssetFileChange $change,
        /** The volume the file was in before the change */
        public Volume $oldVolume,
        /** The file’s path in {@see $oldVolume} before the change */
        public string $oldPath,
    ) {}

    /**
     * Returns the URLs the old file was served from, without revision query parameters.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        if (! $this->oldVolume->sourceHasUrls()) {
            return [];
        }

        $path = implode('/', array_map(rawurlencode(...), explode('/', $this->oldPath)));

        return [$this->oldVolume->sourceDisk()->url($path)];
    }
}
