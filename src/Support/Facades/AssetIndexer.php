<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Generator<int, \CraftCms\Cms\Filesystem\Data\FsListing> getIndexListOnVolume(\CraftCms\Cms\Asset\Data\Volume $volume, string $directory = '')
 * @method static \Illuminate\Support\Collection<array-key, mixed> getExistingIndexingSessions()
 * @method static int removeCliIndexingSessions()
 * @method static \CraftCms\Cms\Asset\Data\IndexingSession|null getIndexingSessionById(int $sessionId)
 * @method static \CraftCms\Cms\Asset\Data\IndexingSession startIndexingSession(array<array-key, mixed> $volumes, bool $cacheRemoteImages = true, bool $listEmptyFolders = false)
 * @method static void stopIndexingSession(\CraftCms\Cms\Asset\Data\IndexingSession $session)
 * @method static \CraftCms\Cms\Asset\Data\IndexingSession createIndexingSession(\CraftCms\Cms\Asset\Data\Volume[] $volumeList, bool $cacheRemoteImages = true, bool $isCli = false, bool $listEmptyFolders = false)
 * @method static int storeIndexList(\Generator $indexList, int $sessionId, \CraftCms\Cms\Asset\Data\Volume $volume)
 * @method static \CraftCms\Cms\Asset\Data\IndexingSession processIndexSession(\CraftCms\Cms\Asset\Data\IndexingSession $indexingSession)
 * @method static string[] getSkippedItemsForSession(\CraftCms\Cms\Asset\Data\IndexingSession $session)
 * @method static array{folders: array<int, string>, files: array<int, string>} getMissingEntriesForSession(\CraftCms\Cms\Asset\Data\IndexingSession $session, string $path = '')
 * @method static \CraftCms\Cms\Asset\Data\AssetIndexEntry|null getNextIndexEntry(\CraftCms\Cms\Asset\Data\IndexingSession $session)
 * @method static void updateIndexEntry(int $entryId, array<array-key, mixed> $data)
 * @method static \CraftCms\Cms\Asset\Elements\Asset indexFile(\CraftCms\Cms\Asset\Data\Volume $volume, string $path, int $sessionId, bool $cacheImages = false, bool $createIfMissing = true)
 * @method static \CraftCms\Cms\Asset\Elements\Asset indexFileByListing(\CraftCms\Cms\Asset\Data\Volume $volume, \CraftCms\Cms\Filesystem\Data\FsListing $listing, int $sessionId, bool $cacheImages = false, bool $createIfMissing = true)
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder indexFolderByListing(\CraftCms\Cms\Asset\Data\Volume $volume, \CraftCms\Cms\Filesystem\Data\FsListing $listing, int $sessionId, bool $createIfMissing = true)
 * @method static \CraftCms\Cms\Asset\Elements\Asset indexFileByEntry(\CraftCms\Cms\Asset\Data\AssetIndexEntry $indexEntry, bool $cacheImages = false, bool $createIfMissing = true)
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder indexFolderByEntry(\CraftCms\Cms\Asset\Data\AssetIndexEntry $indexEntry, bool $createIfMissing = true)
 *
 * @see \CraftCms\Cms\Asset\AssetIndexer
 */
class AssetIndexer extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Asset\AssetIndexer::class;
    }
}
