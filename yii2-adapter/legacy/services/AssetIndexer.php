<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\models\AssetIndexData;
use craft\models\AssetIndexingSession;
use CraftCms\Cms\Asset\AssetIndexer as AssetIndexerService;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIndexStatus;
use CraftCms\Cms\Asset\Models\AssetIndexData as AssetIndexDataModel;
use CraftCms\Cms\Filesystem\Data\FsListing;
use Generator;
use LogicException;
use yii\base\Component;

/**
 * Asset Indexer service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getAssetIndexer()|`Craft::$app->getAssetIndexer()`]].
 *
 * @property-read array $existingIndexingSessions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\AssetIndexer} instead.
 */
class AssetIndexer extends Component
{
    public function getIndexListOnVolume(Volume $volume, string $directory = ''): Generator
    {
        return $this->service()->getIndexListOnVolume($volume, $directory);
    }

    /**
     * @return AssetIndexingSession[]
     */
    public function getExistingIndexingSessions(): array
    {
        return $this->service()->getExistingIndexingSessions()->all();
    }

    public function removeCliIndexingSessions(): int
    {
        return $this->service()->removeCliIndexingSessions();
    }

    public function getIndexingSessionById(int $sessionId): ?AssetIndexingSession
    {
        return $this->service()->getIndexingSessionById($sessionId);
    }

    public function startIndexingSession(
        array $volumes,
        bool $cacheRemoteImages = true,
        bool $listEmptyFolders = false,
    ): AssetIndexingSession {
        return $this->service()->startIndexingSession($volumes, $cacheRemoteImages, $listEmptyFolders);
    }

    public function stopIndexingSession(AssetIndexingSession $session): void
    {
        $this->service()->stopIndexingSession($session);
    }

    /**
     * @param  Volume[]  $volumeList
     */
    public function createIndexingSession(
        array $volumeList,
        bool $cacheRemoteImages = true,
        bool $isCli = false,
        bool $listEmptyFolders = false,
    ): AssetIndexingSession {
        return $this->service()->createIndexingSession($volumeList, $cacheRemoteImages, $isCli, $listEmptyFolders);
    }

    public function storeIndexList(Generator $indexList, int $sessionId, Volume $volume): int
    {
        return $this->service()->storeIndexList($indexList, $sessionId, $volume);
    }

    public function processIndexSession(AssetIndexingSession $indexingSession): AssetIndexingSession
    {
        return $this->service()->processIndexSession($indexingSession);
    }

    /**
     * @return string[]
     */
    public function getSkippedItemsForSession(AssetIndexingSession $session): array
    {
        return $this->service()->getSkippedItemsForSession($session);
    }

    /**
     * @return array{folders: array<int, string>, files: array<int, string>}
     */
    public function getMissingEntriesForSession(AssetIndexingSession $session, string $path = ''): array
    {
        return $this->service()->getMissingEntriesForSession($session, $path);
    }

    public function getNextIndexEntry(AssetIndexingSession $session): ?AssetIndexData
    {
        $entry = $this->service()->getNextIndexEntry($session);

        return $entry ? new AssetIndexData($entry->getAttributes()) : null;
    }

    public function updateIndexEntry(int $entryId, array $data): void
    {
        $entry = AssetIndexDataModel::findOrFail($entryId);
        $status = $entry->getAttribute('status');

        if (!$status instanceof AssetIndexStatus) {
            throw new LogicException("Invalid asset index status for entry $entryId.");
        }

        $legacyFlags = $this->legacyFlags($status);
        $updatedFlags = $legacyFlags;

        foreach (array_keys($legacyFlags) as $flag) {
            if (array_key_exists($flag, $data)) {
                $updatedFlags[$flag] = (bool) $data[$flag];
            }
        }

        $recordId = array_key_exists('recordId', $data) ? (int) $data['recordId'] : null;

        if ($updatedFlags === $legacyFlags && $recordId === null) {
            return;
        }

        $status = match ($updatedFlags) {
            ['inProgress' => false, 'completed' => false, 'isSkipped' => false] => AssetIndexStatus::Pending,
            ['inProgress' => true, 'completed' => false, 'isSkipped' => false] => AssetIndexStatus::Processing,
            ['inProgress' => false, 'completed' => true, 'isSkipped' => false] => AssetIndexStatus::Indexed,
            ['inProgress' => false, 'completed' => true, 'isSkipped' => true] => AssetIndexStatus::Skipped,
            default => throw new LogicException('Invalid legacy asset index status flags.'),
        };

        $this->service()->transitionIndexEntry($entryId, $status, $recordId);
    }

    public function indexFile(
        Volume $volume,
        string $path,
        int $sessionId,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        return $this->service()->indexFile($volume, $path, $sessionId, $cacheImages, $createIfMissing);
    }

    public function indexFileByListing(
        Volume $volume,
        FsListing $listing,
        int $sessionId,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        return $this->service()->indexFileByListing($volume, $listing, $sessionId, $cacheImages, $createIfMissing);
    }

    public function indexFolderByListing(
        Volume $volume,
        FsListing $listing,
        int $sessionId,
        bool $createIfMissing = true,
    ): VolumeFolder {
        return $this->service()->indexFolderByListing($volume, $listing, $sessionId, $createIfMissing);
    }

    public function indexFileByEntry(
        AssetIndexData $indexEntry,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        return $this->service()->indexFileByEntry($indexEntry, $cacheImages, $createIfMissing);
    }

    public function indexFolderByEntry(AssetIndexData $indexEntry, bool $createIfMissing = true): VolumeFolder
    {
        return $this->service()->indexFolderByEntry($indexEntry, $createIfMissing);
    }

    private function service(): AssetIndexerService
    {
        return app(AssetIndexerService::class);
    }

    /** @return array{inProgress: bool, completed: bool, isSkipped: bool} */
    private function legacyFlags(AssetIndexStatus $status): array
    {
        return match ($status) {
            AssetIndexStatus::Pending => ['inProgress' => false, 'completed' => false, 'isSkipped' => false],
            AssetIndexStatus::Processing => ['inProgress' => true, 'completed' => false, 'isSkipped' => false],
            AssetIndexStatus::Indexed => ['inProgress' => false, 'completed' => true, 'isSkipped' => false],
            AssetIndexStatus::Skipped,
            AssetIndexStatus::Missing,
            AssetIndexStatus::Failed => ['inProgress' => false, 'completed' => true, 'isSkipped' => true],
        };
    }
}
