<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\LocalFsInterface;
use craft\elements\Asset;
use craft\errors\AssetDisallowedExtensionException;
use craft\errors\AssetException;
use craft\errors\AssetNotIndexableException;
use craft\errors\FsException;
use craft\errors\MissingAssetException;
use craft\errors\MissingVolumeFolderException;
use craft\errors\MutexException;
use craft\errors\VolumeException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db as DbHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\ImageTransforms;
use craft\models\AssetIndexData;
use craft\models\AssetIndexingSession;
use craft\models\FsListing;
use craft\models\Volume;
use craft\models\VolumeFolder;
use craft\records\AssetIndexingSession as AssetIndexingSessionRecord;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use DateTime;
use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;

/**
 * Asset Indexer service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getAssetIndexer()|`Craft::$app->getAssetIndexer()`]].
 *
 * @property-read array $existingIndexingSessions
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetIndexer extends Component
{
    /**
     * Returns a sorted list of files on a volume.
     *
     * @param Volume $volume The Volume to perform indexing on.
     * @param string $directory Optional path to get index list on a subfolder.
     *
     * @return Generator
     */
    public function getIndexListOnVolume(Volume $volume, string $directory = ''): Generator
    {
        try {
            $fileList = $volume->getFileList($directory);
        } catch (InvalidConfigException|FsException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return;
        }

        $fsSubpath = $volume->getSubpath();
        foreach ($fileList as $listing) {
            $path = $listing->getAdjustedUri($fsSubpath);
            $segments = preg_split('/\\\\|\//', $path);
            $lastSegmentIndex = count($segments) - 1;

            foreach ($segments as $i => $segment) {
                // Ignore if contained in or is a directory beginning with _
                if (str_starts_with($segment, '_') && ($listing->getIsDir() || $i < $lastSegmentIndex)) {
                    continue 2;
                }
            }

            yield $listing;
        }
    }

    /**
     * Return a list of currently active indexing sessions.
     *
     * @return array
     * @since 4.0.0
     */
    public function getExistingIndexingSessions(): array
    {
        $rows = $this->_createAssetIndexingSessionQuery()
            ->where('isCli', false)
            ->get();

        $sessions = [];

        foreach ($rows as $row) {
            $sessions[] = new AssetIndexingSession((array) $row);
        }

        return $sessions;
    }

    /**
     * Remove all CLI-based indexing sessions.
     *
     * @return int
     * @throws DbException
     * @since 4.0.0
     */
    public function removeCliIndexingSessions(): int
    {
        return DB::table(Table::ASSETINDEXINGSESSIONS)
            ->where('isCli', true)
            ->delete();
    }

    /**
     * Get an indexing session by its id.
     *
     * @param int $sessionId
     *
     * @return AssetIndexingSession|null
     * @since 4.0.0
     */
    public function getIndexingSessionById(int $sessionId): ?AssetIndexingSession
    {
        $row = $this->_createAssetIndexingSessionQuery()
            ->where('id', $sessionId)
            ->first();

        if (!$row) {
            return null;
        }

        return new AssetIndexingSession((array) $row);
    }

    /**
     * Start an indexing session for an array of volumes. If first element of array is "all", all volumes wil be indexed.
     *
     * @param array $volumes
     * @param bool $cacheRemoteImages
     * @param bool $listEmptyFolders
     *
     * @return AssetIndexingSession
     * @since 4.0.0
     */
    public function startIndexingSession(
        array $volumes,
        bool $cacheRemoteImages = true,
        bool $listEmptyFolders = false,
    ): AssetIndexingSession {
        $volumeList = [];
        $volumeService = Craft::$app->getVolumes();

        if ($volumes[0] == '*') {
            $volumeList = $volumeService->getAllVolumes();
        } else {
            foreach ($volumes as $volumeId) {
                if ($volume = $volumeService->getVolumeById((int)$volumeId)) {
                    $volumeList[] = $volume;
                }
            }
        }

        $session = $this->createIndexingSession($volumeList, $cacheRemoteImages, listEmptyFolders: $listEmptyFolders);
        $total = 0;

        /** @var Volume $volume */
        foreach ($volumeList as $volume) {
            $fileList = $this->getIndexListOnVolume($volume);
            $total += $this->storeIndexList($fileList, $session->id, $volume);
        }

        if ($total === 0) {
            $session->processIfRootEmpty = true;
        }
        $session->totalEntries = $total;
        $this->storeIndexingSession($session);

        return $session;
    }

    /**
     * Stop an indexing session.
     *
     * @param AssetIndexingSession $session the indexing session to stop.
     *
     * @throws Throwable
     * @since 4.0.0
     */
    public function stopIndexingSession(AssetIndexingSession $session): void
    {
        AssetIndexingSessionRecord::findOne($session->id)?->delete();
    }

    /**
     * Create a new indexing session.
     *
     * @param Volume[] $volumeList
     * @param bool $cacheRemoteImages Whether remote images should be cached.
     * @param bool $isCli Whether indexing is run via CLI
     * @param bool $listEmptyFolders Whether empty folders should be listed for deletion.
     *
     * @return AssetIndexingSession
     * @since 4.0.0
     */
    public function createIndexingSession(
        array $volumeList,
        bool $cacheRemoteImages = true,
        bool $isCli = false,
        bool $listEmptyFolders = false,
    ): AssetIndexingSession {
        $indexedVolumes = [];

        foreach ($volumeList as $volume) {
            $indexedVolumes[$volume->id] = $volume->name;
        }

        $session = new AssetIndexingSession([
            'totalEntries' => 0,
            'indexedVolumes' => Json::encode($indexedVolumes),
            'processedEntries' => 0,
            'cacheRemoteImages' => $cacheRemoteImages,
            'listEmptyFolders' => $listEmptyFolders,
            'actionRequired' => false,
            'isCli' => $isCli,
            'dateUpdated' => null,
            'processIfRootEmpty' => false,
        ]);

        $this->storeIndexingSession($session);

        return $session;
    }

    /**
     * Store an indexing session to DB.
     *
     * @param AssetIndexingSession $session
     */
    protected function storeIndexingSession(AssetIndexingSession $session): void
    {
        if ($session->id !== null) {
            $record = AssetIndexingSessionRecord::findOne($session->id);
        }

        $record ??= new AssetIndexingSessionRecord();

        $record->indexedVolumes = $session->indexedVolumes;
        $record->totalEntries = $session->totalEntries;
        $record->processedEntries = $session->processedEntries;
        $record->cacheRemoteImages = $session->cacheRemoteImages;
        $record->listEmptyFolders = $session->listEmptyFolders;
        $record->actionRequired = $session->actionRequired;
        $record->isCli = $session->isCli;
        $record->processIfRootEmpty = $session->processIfRootEmpty;
        $record->save();

        $session->id = $record->id;
        $session->dateUpdated = DateTimeHelper::toDateTime($record->dateUpdated);
        $session->dateCreated = DateTimeHelper::toDateTime($record->dateCreated);
    }

    /**
     * Store the index list in the index data table.
     *
     * @param Generator $indexList Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @param int $sessionId The indexing session ID.
     * @param Volume $volume The volume.
     *
     * @return int Number of entries inserted
     */
    public function storeIndexList(Generator $indexList, int $sessionId, Volume $volume): int
    {
        $values = [];
        $fsSubpath = $volume->getSubpath();
        $now = now();

        /** @var FsListing $volumeListing */
        foreach ($indexList as $volumeListing) {
            $values[] = [
                'volumeId' => $volume->id,
                'sessionId' => $sessionId,
                'uri' => $volumeListing->getAdjustedUri($fsSubpath),
                'size' => $volumeListing->getFileSize(),
                'timestamp' => !$volumeListing->getIsDir() ? new DateTime('@' . $volumeListing->getDateModified()) : null,
                'isDir' => $volumeListing->getIsDir(),
                'inProgress' => false,
                'completed' => false,
                'dateCreated' => $now,
                'dateUpdated' => $now,
                'uid' => Str::uuid(),
            ];
        }

        return DB::table(Table::ASSETINDEXDATA)->insertOrIgnore($values);
    }

    /**
     * Process an indexing session step.
     *
     * @param AssetIndexingSession $indexingSession
     *
     * @return AssetIndexingSession
     * @throws VolumeException if unable to index file because of volume issue
     * @since 4.0.0
     */
    public function processIndexSession(AssetIndexingSession $indexingSession): AssetIndexingSession
    {
        $lockName = 'idx--' . $indexingSession->id . '--';
        $mutex = Cache::lock($lockName, 3);

        if (!$mutex->get()) {
            throw new MutexException($lockName,
                sprintf('Could not acquire a lock for the indexing session "%s".', $indexingSession->id));
        }

        $indexEntry = $this->getNextIndexEntry($indexingSession);

        // The most likely scenario is that the last entry is being worked on.
        if (!$indexEntry && !$indexingSession->processIfRootEmpty) {
            // if indexEntry is null here, we should also check if there's anything in the assetindexdata table at all
            // (if not, it could have been deleted when clearing caches)
            // if that table is empty, we'll get into an infinite loop, calling processIndexSession with the same data all the time
            // (and it'll be very hard to discard the session via ui)
            if ($indexingSession->processedEntries < $indexingSession->totalEntries) {
                $count = DB::table(Table::ASSETINDEXDATA)
                    ->where([
                        'sessionId' => $indexingSession->id,
                        'completed' => false,
                    ])
                    ->count();

                if ($count === 0) {
                    Craft::warning('The assetindexdata table is empty; Can’t proceed with indexing.');
                    $indexingSession->forceStop = true;
                }
            }

            $mutex->release();
            return $indexingSession;
        }

        // Mark as started.
        if ($indexEntry) {
            $this->updateIndexEntry($indexEntry->id, ['inProgress' => true]);
            $mutex->release();

            try {
                if ($indexEntry->isDir) {
                    $recordId = $this->indexFolderByEntry($indexEntry)->id;
                } else {
                    $recordId = $this->indexFileByEntry($indexEntry, $indexingSession->cacheRemoteImages)->id;
                }

                $this->updateIndexEntry($indexEntry->id,
                    ['completed' => true, 'inProgress' => false, 'recordId' => $recordId]);
            } catch (AssetDisallowedExtensionException|AssetNotIndexableException) {
                $this->updateIndexEntry($indexEntry->id,
                    ['completed' => true, 'inProgress' => false, 'isSkipped' => true]);
            } catch (Throwable $exception) {
                Craft::$app->getErrorHandler()->logException($exception);
                $this->updateIndexEntry($indexEntry->id,
                    ['completed' => true, 'inProgress' => false, 'isSkipped' => true]);
            }

            $session = $this->incrementProcessedEntryCount($indexingSession);
        } else {
            $session = $indexingSession;
        }

        if ($session->processedEntries == $session->totalEntries) {
            $session->actionRequired = true;
            if ($session->processIfRootEmpty) {
                $session->processIfRootEmpty = false;
            }
            $this->storeIndexingSession($session);
        }

        return $indexingSession;
    }

    /**
     * Get skipped items for an indexing session.
     *
     * @param AssetIndexingSession $session
     *
     * @return string[]
     * @since 4.0.0
     */
    public function getSkippedItemsForSession(AssetIndexingSession $session): array
    {
        $skippedItems = DB::table(Table::ASSETINDEXDATA)
            ->select(['volumeId', 'uri'])
            ->where('sessionId', $session->id)
            ->where('isSkipped', true)
            ->get();

        $skipped = [];
        $volumes = Craft::$app->getVolumes();

        foreach ($skippedItems as $skippedItem) {
            $skipped[] = $volumes->getVolumeById((int)$skippedItem->volumeId)->name . '/' . $skippedItem->uri;
        }

        return $skipped;
    }

    /**
     * Get missing entries after an indexing session.
     *
     * @param AssetIndexingSession $session
     * @param string $path
     *
     * @return array with `files` and `folders` keys, containing missing entries.
     * @phpstan-return array{folders:array<int,string>,files:array<int,string>}
     * @throws AssetException
     * @since 4.0.0
     */
    public function getMissingEntriesForSession(AssetIndexingSession $session, string $path = ''): array
    {
        if (!$session->actionRequired) {
            throw new AssetException('A session must be finished before missing entries can be fetched');
        }

        $missing = [
            'folders' => [],
            'files' => [],
        ];

        $cutoff = $session->dateCreated;

        $volumeList = Json::decodeIfJson($session->indexedVolumes);
        if (!$volumeList || !is_array($volumeList)) {
            return $missing;
        }

        $volumeList = array_keys($volumeList);

        $missingFolders = DB::table(Table::VOLUMEFOLDERS, 'folders')
            ->select([
                'folders.path as path',
                'volumes.name as volumeName',
                'volumes.id as volumeId',
                'folders.id as folderId',
            ])
            ->leftJoin(new Alias(Table::VOLUMES, 'volumes'), 'volumes.id', 'folders.volumeId')
            ->where('folders.dateCreated', '<', $cutoff)
            ->whereIn('folders.volumeId', $volumeList)
            ->whereNotNull('folders.parentId')
            ->when(
                $path !== '',
                fn(Builder $query) => $query->where('folders.path', 'like', "$path%")
            )
            ->when(
                !$session->listEmptyFolders,
                fn(Builder $query) => $query
                    ->leftJoin(new Alias(Table::ASSETINDEXDATA, 'indexData'), function(JoinClause $join) {
                        $join->whereColumn('folders.id', 'indexData.recordId')
                            ->where('indexData.isDir', true);
                    })
                    ->whereNull('indexData.id')
            )
            ->get()
            ->map(fn(object $row) => (array) $row);

        $missingFiles = DB::table(Table::ASSETS, 'assets')
            ->select([
                'folders.path as path',
                'volumes.name as volumeName',
                'assets.filename as filename',
                'assets.id as assetId',
            ])
            ->leftJoin(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'assets.id')
            ->leftJoin(new Alias(Table::VOLUMEFOLDERS, 'folders'), 'folders.id', 'assets.folderId')
            ->leftJoin(new Alias(Table::VOLUMES, 'volumes'), 'volumes.id', 'assets.volumeId')
            ->leftJoin(new Alias(Table::ASSETINDEXDATA, 'indexData'), function(JoinClause $join) {
                $join->whereColumn('assets.id', 'indexData.recordId')
                    ->where('indexData.isDir', false);
            })
            ->where('assets.dateCreated', '<', $cutoff)
            ->whereIn('assets.volumeId', $volumeList)
            ->whereNull('elements.dateDeleted')
            ->whereNull('indexData.id')
            ->when(
                $path !== '',
                fn(Builder $query) => $query->where('folders.path', 'like', "$path%")
            )
            ->get()
            ->map(fn(object $row) => (array) $row);

        foreach ($missingFolders as ['folderId' => $folderId, 'path' => $path, 'volumeName' => $volumeName, 'volumeId' => $volumeId]) {
            /**
             * Check to see if the folders are actually empty
             * @link https://github.com/craftcms/cms/issues/11949
             */
            $hasAssets = DB::table(Table::ASSETS, 'assets')
                ->join(new Alias(Table::VOLUMEFOLDERS, 'folders'), 'folders.id', 'assets.folderId')
                ->leftJoin(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'assets.id')
                ->where('assets.volumeId', $volumeId)
                ->whereLike('folders.path', "$path%")
                ->where(function(Builder $query) {
                    $query->whereNull('elements.dateDeleted')
                        ->orWhere('assets.keptFile', 1);
                })
                ->count();

            if ($hasAssets === 0) {
                $missing['folders'][$folderId] = $volumeName . '/' . $path;
            }

            if ($session->listEmptyFolders && $hasAssets > 0) {
                // if the folder contains as many assets as are listed in the $missingFiles
                // allow this folder to be offered for deletion (with the assets in it)
                if ($hasAssets == $missingFiles->filter(fn($file) => str_starts_with($file['path'], $path))->count()) {
                    $missing['folders'][$folderId] = $volumeName . '/' . $path;
                }
            }
        }

        foreach ($missingFiles as ['assetId' => $assetId, 'path' => $path, 'volumeName' => $volumeName, 'filename' => $filename]) {
            $missing['files'][$assetId] = $volumeName . '/' . $path . $filename;
        }

        return $missing;
    }

    /**
     * Returns the next item to index in an indexing session.
     *
     * @param AssetIndexingSession $session
     *
     * @return AssetIndexData|null
     */
    public function getNextIndexEntry(AssetIndexingSession $session): ?AssetIndexData
    {
        $result = DB::table(Table::ASSETINDEXDATA)
            ->select([
                'id',
                'volumeId',
                'sessionId',
                'uri',
                'size',
                'timestamp',
                'isDir',
                'recordId',
                'isSkipped',
                'completed',
                'inProgress',
            ])
            ->where([
                'sessionId' => $session->id,
                'completed' => false,
                'inProgress' => false,
            ])
            ->orderBy('id')
            ->first();

        return $result ? new AssetIndexData((array) $result) : null;
    }

    /**
     * Update indexing-process related data on an index entry.
     *
     * @param int $entryId Index entry ID.
     * @param array $data Key=>value array of data to update.
     */
    public function updateIndexEntry(int $entryId, array $data): void
    {
        // Only allow a few fields to be updated.
        $data = array_intersect_key(
            $data,
            array_flip(['inProgress', 'completed', 'recordId', 'isSkipped', 'processedEntries']),
        );

        DB::table(Table::ASSETINDEXDATA)
            ->where('id', $entryId)
            ->update(array_merge([
                'dateUpdated' => now(),
            ], $data));
    }

    /**
     * Index a single file by Volume and path.
     *
     * @param Volume $volume
     * @param string $path
     * @param int $sessionId The indexing session ID
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     *
     * @return Asset
     * @throws AssetDisallowedExtensionException if attempting to index an asset with a disallowed extension.
     * @throws InvalidConfigException if the volume is misconfigured.
     * @throws MissingAssetException if asset not found and `createIfMissing` set to `false`.
     * @throws VolumeException if unable to read metadata.
     */
    public function indexFile(
        Volume $volume,
        string $path,
        int $sessionId,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        $dirname = dirname($path);
        if (in_array($dirname, ['.', '/', '\\'])) {
            $dirname = '';
        }

        $listing = new FsListing([
            'dirname' => $dirname,
            'basename' => pathinfo($path, PATHINFO_BASENAME),
            'type' => 'file',
            'dateModified' => $volume->getDateModified($path),
            'fileSize' => $volume->getFileSize($path),
        ]);

        return $this->indexFileByListing($volume, $listing, $sessionId, $cacheImages, $createIfMissing);
    }

    /**
     * @param Volume $volume
     * @param FsListing $listing
     * @param int $sessionId
     * @param bool $cacheImages
     * @param bool $createIfMissing
     *
     * @return Asset
     * @throws AssetDisallowedExtensionException if attempting to index an asset with a disallowed extension
     * @throws VolumeException
     * @throws InvalidConfigException
     * @throws MissingVolumeFolderException
     * @throws MissingAssetException if asset not found and `createIfMissing` set to `false`.
     * @since 4.0.0
     */
    public function indexFileByListing(
        Volume $volume,
        FsListing $listing,
        int $sessionId,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        $indexEntry = new AssetIndexData([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getAdjustedUri($volume->getSubpath()),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
            'inProgress' => true,
        ]);

        $asset = $this->indexFileByEntry($indexEntry, $cacheImages, $createIfMissing);
        $indexEntry->recordId = $asset->id;
        $this->storeIndexEntry($indexEntry);
        return $asset;
    }

    /**
     * @param Volume $volume
     * @param FsListing $listing
     * @param int $sessionId
     * @param bool $createIfMissing
     *
     * @return VolumeFolder
     * @throws AssetNotIndexableException
     * @throws VolumeException
     * @since 4.0.0
     */
    public function indexFolderByListing(
        Volume $volume,
        FsListing $listing,
        int $sessionId,
        bool $createIfMissing = true,
    ): VolumeFolder {
        $indexEntry = new AssetIndexData([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getAdjustedUri($volume->getSubpath()),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
            'inProgress' => true,
        ]);

        $folder = $this->indexFolderByEntry($indexEntry, $createIfMissing);
        $indexEntry->recordId = $folder->id;
        $this->storeIndexEntry($indexEntry);
        return $folder;
    }

    /**
     * Store a single index entry.
     *
     * @param AssetIndexData $indexEntry
     *
     * @throws DbException
     * @since 4.0.5
     */
    protected function storeIndexEntry(AssetIndexData $indexEntry)
    {
        $now = now();

        $data = [
            'sessionId' => $indexEntry->sessionId,
            'volumeId' => $indexEntry->volumeId,
            'uri' => $indexEntry->uri,
            'size' => $indexEntry->size,
            'timestamp' => $indexEntry->timestamp,
            'isDir' => $indexEntry->isDir,
            'recordId' => $indexEntry->recordId,
            'isSkipped' => $indexEntry->isSkipped,
            'inProgress' => $indexEntry->inProgress,
            'completed' => $indexEntry->completed,
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => Str::uuid(),
        ];

        if ($indexEntry->id) {
            $data['id'] = $indexEntry->id;
        }

        DB::table(Table::ASSETINDEXDATA)
            ->insert($data);
    }

    /**
     * Indexes a file by its index entry.
     *
     * @param AssetIndexData $indexEntry
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     *
     * @return Asset
     * @throws AssetDisallowedExtensionException If the file being indexed has a disallowed extension
     * @throws InvalidConfigException
     * @throws MissingAssetException
     * @throws VolumeException
     */
    public function indexFileByEntry(
        AssetIndexData $indexEntry,
        bool $cacheImages = false,
        bool $createIfMissing = true,
    ): Asset {
        // Determine the parent folder
        $uriPath = $indexEntry->uri;
        $dirname = dirname($uriPath);

        // Check if in a directory that cannot be indexed
        foreach (preg_split('/\\\\|\//', $dirname) as $part) {
            if ($part[0] === '_') {
                throw new AssetNotIndexableException("File “{$indexEntry->uri}” is in a directory that cannot be indexed.");
            }
        }

        $extension = pathinfo($indexEntry->uri, PATHINFO_EXTENSION);
        $filename = basename($indexEntry->uri);

        // Check if filename is allowed and extension are allowed
        if (preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $filename)) {
            throw new AssetNotIndexableException("File “{$indexEntry->uri}” will not be indexed.");
        }

        if (!in_array(strtolower($extension), Cms::config()->allowedFileExtensions, true)) {
            throw new AssetDisallowedExtensionException("File “{$indexEntry->uri}” was not indexed because extension “{$extension}” is not allowed.");
        }

        if ($dirname === '.') {
            $parentId = ':empty:';
            $path = '';
        } else {
            $parentId = false;
            $path = $dirname . '/';
        }

        $assets = Craft::$app->getAssets();
        $folder = $assets->findFolder([
            'volumeId' => $indexEntry->volumeId,
            'path' => $path,
            'parentId' => $parentId,
        ]);

        if (!$folder) {
            /** @var Volume $volume */
            $volume = Craft::$app->getVolumes()->getVolumeById($indexEntry->volumeId);
            $folder = $assets->ensureFolderByFullPathAndVolume($path, $volume);
        } else {
            $volume = $folder->getVolume();
        }

        $fs = $volume->getFs();

        $folderId = $folder->id;

        /** @var Asset|null $asset */
        $asset = Asset::find()
            ->filename(DbHelper::escapeParam($filename))
            ->folderId($folderId)
            ->one();

        // Create an asset if there is none.
        if (!$asset) {
            if (!$createIfMissing) {
                throw new MissingAssetException($indexEntry, $volume, $folder, $filename);
            }

            $asset = new Asset();
            $asset->setVolumeId((int)$volume->id);
            $asset->folderId = $folderId;
            $asset->folderPath = $folder->path;
            $asset->setFilename($filename);
            $asset->kind = AssetsHelper::getFileKindByExtension($filename);
        }

        $asset->size = $indexEntry->size;
        $timeModified = $indexEntry->timestamp;

        $asset->setScenario(Asset::SCENARIO_INDEX);

        try {
            if ($fs instanceof LocalFsInterface) {
                // Have the asset store its MIME type, since it will be able to get it from its file info
                $asset->setMimeType($asset->getMimeType());
            }

            // All sorts of fun stuff for images.
            if ($asset->kind === Asset::KIND_IMAGE) {
                $dimensions = null;
                $tempPath = null;

                // For local images it's easy - the image is right there, nothing to cache and the asset ID means nothing.
                if ($fs instanceof LocalFsInterface) {
                    $transformSourcePath = $asset->getImageTransformSourcePath();
                    $dimensions = Image::imageSize($transformSourcePath);
                } else {
                    // If we don't have to cache, then we don't really care if the asset ID is there.
                    if (!$cacheImages) {
                        try {
                            // Get the stream
                            $stream = $asset->getStream();

                            // And, well, try to read as little data as we can.
                            if (is_resource($stream)) {
                                $dimensions = Image::imageSizeByStream($stream);
                                fclose($stream);
                            }
                        } catch (VolumeException $e) {
                            Craft::info($e->getMessage());
                        }
                    }

                    // if $dimensions is not an array by now, either smart-guessing failed or the user wants to cache this.
                    if (!is_array($dimensions)) {
                        $tempPath = AssetsHelper::tempFilePath(pathinfo($filename, PATHINFO_EXTENSION));
                        AssetsHelper::downloadFile($volume, $indexEntry->uri, $tempPath);
                        $dimensions = Image::imageSize($tempPath);

                        // Store the MIME type on the asset so long as we have it downloaded
                        $asset->setMimeType(FileHelper::getMimeType($tempPath));
                    }
                }

                [$w, $h] = $dimensions;
                $asset->setWidth($w);
                $asset->setHeight($h);
                $asset->dateModified = $timeModified;

                Craft::$app->getElements()->saveElement($asset);

                // Now we definitely have an asset ID, so let's cover one last base.
                $shouldCache = !$fs instanceof LocalFsInterface && $cacheImages && Cms::config()->maxCachedCloudImageSize > 0;

                if ($shouldCache && $tempPath) {
                    $targetPath = $asset->getImageTransformSourcePath();
                    ImageTransforms::storeLocalSource($tempPath, $targetPath);
                    FileHelper::unlink($tempPath);
                }
            } else {
                // For images, the asset has been saved already to ensure an ID was in place.
                $asset->dateModified = $timeModified;
                Craft::$app->getElements()->saveElement($asset);
            }
        } catch (Throwable $exception) {
            // Log an exception and pretend we're cool
            Craft::warning($exception->getMessage());
        }

        return $asset;
    }

    /**
     * Indexes a folder by its index entry.
     *
     * @param AssetIndexData $indexEntry
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     *
     * @return VolumeFolder
     * @throws VolumeException
     * @throws AssetNotIndexableException
     * @throws MissingVolumeFolderException
     * @since 4.0.0
     */
    public function indexFolderByEntry(AssetIndexData $indexEntry, bool $createIfMissing = true): VolumeFolder
    {
        if ($indexEntry->uri !== null) {
            foreach (preg_split('/\\\\|\//', $indexEntry->uri) as $part) {
                if ($part[0] === '_') {
                    throw new AssetNotIndexableException("The directory “{$indexEntry->uri}” cannot be indexed.");
                }
            }
        }

        $folder = Craft::$app->getAssets()->findFolder([
            'path' => $indexEntry->uri . '/',
            'volumeId' => $indexEntry->volumeId,
        ]);

        /** @var Volume $volume */
        $volume = Craft::$app->getVolumes()->getVolumeById($indexEntry->volumeId);

        if (!$folder && !$createIfMissing) {
            throw new MissingVolumeFolderException($indexEntry, $volume, $indexEntry->uri);
        }

        return Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($indexEntry->uri ?? '', $volume);
    }

    /**
     * Increment the processed entry count on a session.
     *
     * @param AssetIndexingSession $session
     *
     * @return AssetIndexingSession
     * @throws Exception
     */
    protected function incrementProcessedEntryCount(AssetIndexingSession $session): AssetIndexingSession
    {
        // Make SURE the counter proceeds correctly across multiple indexing jobs.
        $lockName = 'idx--update-' . $session->id . '--';
        $mutex = Cache::lock($lockName, 5);

        if (!$mutex->get()) {
            throw new MutexException($lockName,
                sprintf('Could not acquire a lock for the indexing session "%s".', $session->id));
        }

        /** @var AssetIndexingSessionRecord $record */
        $record = AssetIndexingSessionRecord::findOne($session->id);
        $record->processedEntries++;
        $record->save();
        $mutex->release();

        $session->processedEntries = (int)$record->processedEntries;

        return $session;
    }

    private function _createAssetIndexingSessionQuery(): Builder
    {
        return DB::table(Table::ASSETINDEXINGSESSIONS)
            ->select([
                'id',
                'indexedVolumes',
                'totalEntries',
                'processedEntries',
                'cacheRemoteImages',
                'listEmptyFolders',
                'isCli',
                'actionRequired',
                'processIfRootEmpty',
                'dateCreated',
                'dateUpdated',
            ]);
    }
}
