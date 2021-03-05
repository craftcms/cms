<?php

namespace craft\services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\AssetDisallowedExtensionException;
use craft\errors\AssetException;
use craft\errors\MissingAssetException;
use craft\errors\MissingVolumeFolderException;
use craft\errors\VolumeException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\models\AssetIndexData;
use craft\models\AssetIndexingSession;
use craft\models\VolumeFolder;
use craft\models\VolumeListing;
use craft\records\AssetIndexData as AssetIndexDataRecord;
use craft\records\AssetIndexingSession as AssetIndexingSessionRecord;
use Generator;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class AssetIndexer
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license http://craftcms.com/license Craft License Agreement
 * @see http://craftcms.com
 * @package craft.app.services
 * @since 3.0.0
 *
 * @property-read array $existingIndexingSessions
 */
class AssetIndexer extends Component
{
    /**
     * Returns a sorted list of files on a volume.
     *
     * @param VolumeInterface $volume The Volume to perform indexing on.
     * @param string $directory Optional path to get index list on a subfolder.
     * @return Generator|VolumeListing[]
     */
    public function getIndexListOnVolume(VolumeInterface $volume, string $directory = ''): Generator
    {
        try {
            $fileList = $volume->getFileList($directory);
        } catch (VolumeException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return;
        }

        foreach ($fileList as $listing) {
            $path = $listing->getUri();
            $segments = explode('/', $path);
            $lastSegmentIndex = count($segments) - 1;

            foreach ($segments as $i => $segment) {
                // Ignore if contained in or is a directory beginning with _
                if (strpos($segment, '_') === 0 && ($listing->getIsDir() || $i < $lastSegmentIndex)) {
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
            ->where(['isCli' => false])
            ->all();

        $sessions = [];

        foreach ($rows as $row) {
            $sessions[] = new AssetIndexingSession($row);
        }

        return $sessions;
    }

    /**
     * Remove all CLI-based indexing sessions.
     *
     * @return int
     * @throws \yii\db\Exception
     * @since 4.0.0
     */
    public function removeCliIndexingSessions(): int
    {
        return Db::delete(Table::ASSETINDEXINGSESSIONS, [
            'isCli' => true
        ]);
    }

    /**
     * Get an indexing session by its id.
     *
     * @param int $sessionId
     * @return AssetIndexingSession|null
     * @since 4.0.0
     */
    public function getIndexingSessionById(int $sessionId): ?AssetIndexingSession
    {
        $query = $this->_createAssetIndexingSessionQuery();
        $row = $query->where(['id' => $sessionId])->one();

        if (!$row) {
            return null;
        }

        return new AssetIndexingSession($row);
    }

    /**
     * Start an indexing session for an array of volumes. If first element of array is "all", all volumes wil be indexed.
     *
     * @param array $volumes
     * @param bool $cacheRemoteImages
     * @return AssetIndexingSession
     * @since 4.0.0
     */
    public function startIndexingSession(array $volumes, bool $cacheRemoteImages = true): AssetIndexingSession
    {
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

        $session = $this->createIndexingSession($volumeList, $cacheRemoteImages);
        $total = 0;

        /** @var VolumeInterface $volume */
        foreach ($volumeList as $volume) {
            try {
                $fileList = $volume->getFileList();
            } catch (VolumeException $e) {
                Craft::warning('Unable to list files in ' . $volume->handle . '.');
                continue;
            }

            $total += $this->storeIndexList($fileList, $session->id, $volume->id);
        }

        $session->totalEntries = $total;
        $this->storeIndexingSession($session);

        return $session;
    }

    /**
     * Stop an indexing session.
     *
     * @param AssetIndexingSession $session the indexing session to stop.
     * @throws \Throwable
     * @since 4.0.0
     */
    public function stopIndexingSession(AssetIndexingSession $session): void
    {
        $sessionRecord = AssetIndexingSessionRecord::findOne($session->id);

        if ($sessionRecord) {
            $sessionRecord->delete();
        }
    }

    /**
     * Create a new indexing session.
     *
     * @param VolumeInterface[] $volumeList
     * @param bool $cacheRemoteImages Whether remote images should be cached.
     * @param bool $isCli Whether indexing is run via CLI
     * @return AssetIndexingSession
     * @since 4.0.0
     */
    public function createIndexingSession(array $volumeList, bool $cacheRemoteImages = true, $isCli = false): AssetIndexingSession
    {
        $indexedVolumes = [];

        foreach ($volumeList as $volume) {
            $indexedVolumes[$volume->id] = $volume->name;
        }

        $session = new AssetIndexingSession([
            'totalEntries' => 0,
            'indexedVolumes' => Json::encode($indexedVolumes),
            'processedEntries' => 0,
            'cacheRemoteImages' => $cacheRemoteImages,
            'actionRequired' => false,
            'isCli' => $isCli,
            'dateUpdated' => null,
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

        $record = $record ?? new AssetIndexingSessionRecord();

        $record->indexedVolumes = $session->indexedVolumes;
        $record->totalEntries = $session->totalEntries;
        $record->processedEntries = $session->processedEntries;
        $record->cacheRemoteImages = $session->cacheRemoteImages;
        $record->actionRequired = $session->actionRequired;
        $record->isCli = $session->isCli;
        $record->save();

        $session->id = $record->id;
        $session->dateUpdated = DateTimeHelper::toDateTime($record->dateUpdated);
        $session->dateCreated = DateTimeHelper::toDateTime($record->dateCreated);
    }

    /**
     * Store the index list in the index data table.
     *
     * @param Generator $indexList Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @param int $sessionId Session id.
     * @param int $volumeId Volume id.
     * @return int Number of entries inserted
     */
    public function storeIndexList(Generator $indexList, int $sessionId, int $volumeId): int
    {
        $attributes = ['volumeId', 'sessionId', 'uri', 'size', 'timestamp', 'isDir', 'inProgress', 'completed'];
        $values = [];

        /** @var VolumeListing $volumeListing */
        foreach ($indexList as $volumeListing) {
            $values[] = [
                $volumeId,
                $sessionId,
                $volumeListing->getUri(),
                $volumeListing->getFileSize(),
                Db::prepareDateForDb(new \DateTime('@' . $volumeListing->getDateModified())),
                $volumeListing->getIsDir(),
                false,
                false
            ];
        }

        return Db::batchInsert(Table::ASSETINDEXDATA, $attributes, $values);
    }

    /**
     * Process an indexing session step.
     *
     * @param AssetIndexingSession $indexingSession
     * @return AssetIndexingSession
     * @throws VolumeException if unable to index file because of Volume issue
     * @since 4.0.0
     */
    public function processIndexSession(AssetIndexingSession $indexingSession): AssetIndexingSession
    {
        $mutex = Craft::$app->getMutex();
        $lockName = 'idx--' . $indexingSession->id . '--';

        if (!$mutex->acquire($lockName, 5)) {
            throw new Exception('Could not acquire a lock for the indexing session "' . $indexingSession->id . '".');
        }

        $indexEntry = $this->getNextIndexEntry($indexingSession);

        // The most likely scenario is that the last entry is being worked on.
        if (!$indexEntry) {
            $mutex->release($lockName);
            return $indexingSession;
        }

        // Mark as started.
        $this->updateIndexEntry($indexEntry->id, ['inProgress' => true]);
        $mutex->release($lockName);

        try {
            if ($indexEntry->isDir) {
                /** @var VolumeInterface $volume */
                $volume = Craft::$app->getVolumes()->getVolumeById($indexEntry->volumeId);
                $recordId = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($indexEntry->uri, $volume)->id;
            } else {
                $recordId = $this->_indexFileByIndexData($indexEntry, true, $indexingSession->cacheRemoteImages)->id;
            }

            $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'recordId' => $recordId]);
        } catch (AssetDisallowedExtensionException $exception) {
            $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'isSkipped' => true]);
        }

        $session = $this->incrementProcessedEntryCount($indexingSession);

        if ($session->processedEntries == $session->totalEntries) {
            $session->actionRequired = true;
            $this->storeIndexingSession($session);
        }

        return $indexingSession;
    }

    /**
     * Get skipped items for an indexing session.
     *
     * @param AssetIndexingSession $session
     * @return array
     * @since 4.0.0
     */
    public function getSkippedItemsForSession(AssetIndexingSession $session): array
    {
        $skippedItems = (new Query())
            ->select(['volumeId', 'uri'])
            ->where(['sessionId' => $session->id])
            ->andWhere(['isSkipped' => true])
            ->from([Table::ASSETINDEXDATA])
            ->all();

        $skipped = [];
        $volumes = Craft::$app->getVolumes();

        foreach ($skippedItems as ['volumeId' => $volumeId, 'uri' => $uri]) {
            $skipped[] = $volumes->getVolumeById($volumeId)->name . '/' . $uri;
        }

        return $skipped;
    }

    /**
     * Get missing entries after an indexing session.
     *
     * @param AssetIndexingSession $session
     * @return array with `files` and `folders` keys, containing missing entries.
     * @throws AssetException
     * @since 4.0.0
     */
    public function getMissingEntriesForSession(AssetIndexingSession $session): array
    {
        if (!$session->actionRequired) {
            throw new AssetException('A session must be finished before missing entries can be fetched');
        }

        $cutoff = Db::prepareDateForDb($session->dateCreated);

        $volumeList = Json::decodeIfJson($session->indexedVolumes);
        if (!$volumeList || !is_array($volumeList)) {
            return [];
        }

        $volumeList = array_keys($volumeList);

        $missingFolders = (new Query())
            ->select(['path' => 'folders.path', 'volumeName' => 'volumes.name', 'folderId' => 'folders.id'])
            ->from(['folders' => Table::VOLUMEFOLDERS])
            ->leftJoin(['volumes' => Table::VOLUMES], '[[volumes.id]] = [[folders.volumeId]]')
            ->leftJoin(['indexData' => Table::ASSETINDEXDATA], ['and', '[[folders.id]] = [[indexData.recordId]]', ['indexData.isDir' => true]])
            ->where(['<', 'folders.dateCreated', $cutoff])
            ->andWhere(['folders.volumeId' => $volumeList])
            ->andWhere(['not', ['folders.parentId' => null]])
            ->andWhere(['indexData.id' => null]);

        $missingFolders = $missingFolders
            ->all();

        $missingFiles = (new Query())
            ->select(['path' => 'folders.path', 'volumeName' => 'volumes.name', 'filename' => 'assets.filename', 'assetId' => 'assets.id'])
            ->from(['assets' => Table::ASSETS])
            ->leftJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[assets.id]]')
            ->leftJoin(['folders' => Table::VOLUMEFOLDERS], '[[folders.id]] = [[assets.folderId]]')
            ->leftJoin(['volumes' => Table::VOLUMES], '[[volumes.id]] = [[assets.volumeId]]')
            ->leftJoin(['indexData' => Table::ASSETINDEXDATA], ['and', '[[assets.id]] = [[indexData.recordId]]', ['indexData.isDir' => false]])
            ->where(['<', 'assets.dateCreated', $cutoff])
            ->andWhere(['assets.volumeId' => $volumeList])
            ->andWhere(['elements.dateDeleted' => null])
            ->andWhere(['indexData.id' => null])
            ->all();

        $missing = [
            'folders' => [],
            'files' => []
        ];

        foreach ($missingFolders as ['folderId' => $folderId, 'path' => $path, 'volumeName' => $volumeName]) {
            $missing['folders'][$folderId] = $volumeName . '/' . $path;
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
     * @return AssetIndexData|null
     */
    public function getNextIndexEntry(AssetIndexingSession $session): ?AssetIndexData
    {
        $result = (new Query())
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
            ->from([Table::ASSETINDEXDATA])
            ->where([
                'sessionId' => $session->id,
                'completed' => false,
                'inProgress' => false
            ])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $result ? new AssetIndexData($result) : null;
    }

    /**
     * Update indexing-process related data on an index entry.
     *
     * @param int $entryId Index entry id.
     * @param array $data Key=>value array of data to update.
     */
    public function updateIndexEntry(int $entryId, array $data): void
    {
        // Only allow a few fields to be updated.
        $data = array_intersect_key($data, array_flip(['inProgress', 'completed', 'recordId', 'isSkipped', 'processedEntries']));
        Db::update(Table::ASSETINDEXDATA, $data, [
            'id' => $entryId,
        ]);
    }

    /**
     * Index a single file by Volume and path.
     *
     * @param VolumeInterface $volume
     * @param string $path
     * @param int $sessionId optional indexing session id.
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     * @return Asset
     * @throws AssetDisallowedExtensionException if attempting to index an Asset with a disallowed extension
     * @throws InvalidConfigException if misconfigured volume
     * @throws MissingAssetException if asset not found and `createIfMissing` set to `false`.
     * @throws VolumeException if unable to read metadata.
     */
    public function indexFile(VolumeInterface $volume, string $path, int $sessionId, bool $cacheImages = false, bool $createIfMissing = true): Asset
    {
        $listing = new VolumeListing([
            'dirname' => $path,
            'basename' => pathinfo($path, PATHINFO_BASENAME),
            'type' => 'file',
            'dateModified' => $volume->getDateModified($path),
            'fileSize' => $volume->getFileSize($path),
            'volume' => $volume
        ]);

        return $this->indexFileByListing($listing, $sessionId, $cacheImages, $createIfMissing);
    }

    /**
     * @param VolumeListing $listing
     * @param int $sessionId
     * @param bool $cacheImages
     * @param bool $createIfMissing
     * @return Asset
     * @throws AssetDisallowedExtensionException if attempting to index an Asset with a disallowed extension
     * @throws MissingAssetException if asset not found and `createIfMissing` set to `false`.
     * @since 4.0.0
     */
    public function indexFileByListing(VolumeListing $listing, int $sessionId, bool $cacheImages = false, bool $createIfMissing = true): Asset
    {
        $volume = $listing->getVolume();
        $indexEntry = new AssetIndexData([
            'id' => null,
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getUri(),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
            'recordId' => null,
            'inProgress' => true,
            'isSkipped' => null,
            'completed' => false
        ]);

        return $this->indexFileByEntry($indexEntry, $cacheImages, $createIfMissing);
    }

    /**
     * @param VolumeListing $listing
     * @param int $sessionId
     * @param bool $createIfMissing
     * @return VolumeFolder
     * @throws MissingVolumeFolderException if asset not found and `createIfMissing` set to `false`.
     * @since 4.0.0
     */
    public function indexFolderByListing(VolumeListing $listing, int $sessionId, bool $createIfMissing = true): VolumeFolder
    {
        $volume = $listing->getVolume();
        $indexEntry = new AssetIndexData([
            'id' => null,
            'volumeId' => $volume->id,
            'sessionId' => $sessionId,
            'uri' => $listing->getUri(),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'isDir' => $listing->getIsDir(),
            'recordId' => null,
            'inProgress' => true,
            'isSkipped' => null,
            'completed' => false
        ]);

        return $this->indexFolderByEntry($indexEntry, $createIfMissing);
    }

    /**
     * Indexes a file by its index entry.
     *
     * @param AssetIndexData $indexEntry
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     * @return Asset
     * @throws AssetDisallowedExtensionException If the file being indexed has a disallowed extension
     * @throws InvalidConfigException
     * @throws MissingAssetException
     * @throws VolumeException
     */
    public function indexFileByEntry(AssetIndexData $indexEntry, bool $cacheImages = false, bool $createIfMissing = true): Asset
    {
        $indexEntry->inProgress = true;
        $indexEntry->completed = false;
        $recordData = $indexEntry->toArray();

        $record = new AssetIndexDataRecord($recordData);
        $record->save();
        $indexEntry->id = $record->id;

        try {
            $asset = $this->_indexFileByIndexData($indexEntry, $createIfMissing, $cacheImages);
            $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'recordId' => $asset->id]);
        } catch (AssetDisallowedExtensionException $exception) {
            $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'isSkipped' => true]);
            throw $exception;
        }

        return $asset;
    }

    /**
     * Indexes a folder by its index entry.
     *
     * @param AssetIndexData $indexEntry
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     * @return VolumeFolder
     * @throws VolumeException
     * @since 4.0.0
     */
    public function indexFolderByEntry(AssetIndexData $indexEntry, bool $createIfMissing = true): VolumeFolder
    {
        $indexEntry->inProgress = true;
        $indexEntry->completed = false;
        $recordData = $indexEntry->toArray();

        $record = new AssetIndexDataRecord($recordData);
        $record->save();
        $indexEntry->id = $record->id;

        $folder = Craft::$app->getAssets()->findFolder(['path' => $indexEntry->uri . '/', 'volumeId' => $indexEntry->volumeId]);

        /** @var VolumeInterface $volume */
        $volume = Craft::$app->getVolumes()->getVolumeById($indexEntry->volumeId);

        if (!$folder && !$createIfMissing) {
            throw new MissingVolumeFolderException($indexEntry, $volume, $indexEntry->uri);
        }

        $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($indexEntry->uri, $volume);
        $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'recordId' => $folder->id]);

        return $folder;
    }

    /**
     * Increment the processed entry count on a session.
     *
     * @param AssetIndexingSession $session
     * @return AssetIndexingSession
     * @throws Exception
     */
    protected function incrementProcessedEntryCount(AssetIndexingSession $session): AssetIndexingSession
    {
        // Make SURE the counter proceeds correctly across multiple indexing jobs.
        $mutex = Craft::$app->getMutex();
        $lockName = 'idx--update-' . $session->id . '--';

        if (!$mutex->acquire($lockName, 5)) {
            throw new Exception('Could not acquire a lock for the indexing session "' . $session->id . '".');
        }

        /** @var AssetIndexingSessionRecord $record */
        $record = AssetIndexingSessionRecord::findOne($session->id);
        $record->processedEntries++;
        $record->save();
        $mutex->release($lockName);

        $session->processedEntries = (int)$record->processedEntries;

        return $session;
    }

    /**
     * Indexes a file.
     *
     * @param AssetIndexData $indexEntry Asset Index Data entry that contains information for the Asset-to-be.
     * @param bool $createIfMissing Whether the asset record should be created if none exists
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @return Asset
     * @throws AssetDisallowedExtensionException if the extension of the file is not allowed.
     * @throws MissingAssetException
     * @throws VolumeException
     */
    private function _indexFileByIndexData(AssetIndexData $indexEntry, bool $createIfMissing = true, bool $cacheImages = false): Asset
    {
        // Determine the parent folder
        $uriPath = $indexEntry->uri;
        $dirname = dirname($uriPath);

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
            'parentId' => $parentId
        ]);

        if (!$folder) {
            /** @var VolumeInterface $volume */
            $volume = Craft::$app->getVolumes()->getVolumeById($indexEntry->volumeId);
            $folder = $assets->ensureFolderByFullPathAndVolume($path, $volume);
        } else {
            $volume = $folder->getVolume();
        }

        // Check if the extension is allowed
        $extension = pathinfo($indexEntry->uri, PATHINFO_EXTENSION);
        $filename = basename($indexEntry->uri);

        if (!in_array(strtolower($extension), Craft::$app->getConfig()->getGeneral()->allowedFileExtensions, true)) {
            throw new AssetDisallowedExtensionException("File “{$indexEntry->uri}” was not indexed because extension “{$extension}” is not allowed.");
        }

        $folderId = $folder->id;

        /** @var Asset $asset */
        $asset = Asset::find()
            ->filename(Db::escapeParam($filename))
            ->folderId($folderId)
            ->one();

        // Create an Asset if there is none.
        if (!$asset) {
            if (!$createIfMissing) {
                throw new MissingAssetException($indexEntry, $volume, $folder, $filename);
            }

            $asset = new Asset();
            $asset->setVolumeId($volume->id);
            $asset->folderId = $folderId;
            $asset->folderPath = $folder->path;
            $asset->filename = $filename;
            $asset->kind = AssetsHelper::getFileKindByExtension($filename);
        }

        $asset->size = $indexEntry->size;
        $timeModified = $indexEntry->timestamp;

        $asset->setScenario(Asset::SCENARIO_INDEX);

        try {
            // All sorts of fun stuff for images.
            if ($asset->kind === Asset::KIND_IMAGE) {
                $dimensions = null;
                $tempPath = null;

                // For local images it's easy - the image is right there, nothing to cache and the Asset id means nothing.
                if ($volume instanceof LocalVolumeInterface) {
                    $transformSourcePath = $asset->getImageTransformSourcePath();
                    $dimensions = Image::imageSize($transformSourcePath);
                } else {
                    // If we don't have to cache, then we don't really care if the Asset id is there.
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
                    }
                }

                [$w, $h] = $dimensions;
                $asset->setWidth($w);
                $asset->setHeight($h);
                $asset->dateModified = $timeModified;

                Craft::$app->getElements()->saveElement($asset);

                // Now we definitely have an Asset id, so let's cover one last base.
                if (!$volume instanceof LocalVolumeInterface && $cacheImages && $tempPath) {
                    $targetPath = $asset->getImageTransformSourcePath();
                    $assetTransforms = Craft::$app->getAssetTransforms();
                    $assetTransforms->storeLocalSource($tempPath, $targetPath);
                    $assetTransforms->queueSourceForDeletingIfNecessary($targetPath);
                    FileHelper::unlink($tempPath);
                }
            } else {
                // For images, the Asset has been saved already to ensure an ID was in place.
                $asset->dateModified = $timeModified;
                Craft::$app->getElements()->saveElement($asset);
            }
        } catch (\Throwable $exception) {
            // Log an exception and pretend we're cool
            Craft::warning($exception->getMessage());
        }

        return $asset;
    }

    /**
     * @return Query
     */
    private function _createAssetIndexingSessionQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'indexedVolumes',
                'totalEntries',
                'processedEntries',
                'cacheRemoteImages',
                'isCli',
                'actionRequired',
                'dateCreated',
                'dateUpdated'
            ])
            ->from(Table::ASSETINDEXINGSESSIONS);
    }
}
