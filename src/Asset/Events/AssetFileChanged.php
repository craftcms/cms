<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetFileChange;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after an existing asset’s file is replaced, moved, deleted or restored from the trash, once the change
 * is committed.
 *
 * It doesn’t fire for new assets, or when an asset is deleted with `keepFileOnDelete` set. Caches can use
 * {@see urls()} to purge the URL the file was served from, or for a restored file, the URL it’s served from again.
 *
 * The file is changed before the save is committed, and disks aren’t part of the transaction. If the save is rolled
 * back after that, this isn’t dispatched, even though the file may already have been moved or replaced.
 *
 * @since 6.0.0
 */
class AssetFileChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        /** The asset, as it is after the change */
        public Asset $asset,
        public AssetFileChange $change,
        /** The volume the file was in before the change, or for a restored file, the volume it was restored to */
        public Volume $oldVolume,
        /** The file’s path in {@see $oldVolume} before the change, or for a restored file, the path it was restored to */
        public string $oldPath,
        /** The URL {@see Asset::getUrl()} returned before the change, or for a restored file, after it */
        public ?string $oldUrl = null,
    ) {}

    /**
     * Returns the URLs the old file was served from, or a restored file is served from again: the URL as it was
     * served, and the same URL without its query string.
     *
     * The served URL is the one {@see Asset::getUrl()} returned, so it includes a `v` revision parameter when the
     * `revAssetUrls` config setting is on, and any URL a plugin set with
     * {@see AssetUrlResolving} or {@see AssetUrlDefined}. Older `v` values, from earlier file dates or focal points,
     * can’t be recovered; URLs with them expire on the CDN’s own TTL.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        $urls = [];

        if ($this->oldUrl !== null) {
            $urls[] = $this->oldUrl;
        }

        if ($this->oldVolume->sourceHasUrls()) {
            $path = implode('/', array_map(rawurlencode(...), explode('/', $this->oldPath)));
            $urls[] = Html::encodeSpaces($this->oldVolume->sourceDisk()->url($path));
        }

        return array_values(array_unique($urls));
    }
}
