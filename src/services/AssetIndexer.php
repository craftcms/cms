<?php

namespace craft\services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\AssetDisallowedExtensionException;
use craft\errors\MissingAssetException;
use craft\errors\VolumeException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\models\AssetIndexData;
use craft\models\AssetIndexingSession;
use craft\models\VolumeListing;
use craft\records\AssetIndexData as AssetIndexDataRecord;
use Generator;
use Spatie\Ray\Ray;
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
 */
class AssetIndexer extends Component
{
    /**
     * Returns a unique indexing session id.
     *
     * @return string
     */
    public function getIndexingSessionId(): string
    {
        return StringHelper::UUID();
    }

    /**
     * Gets the index list for a volume.
     *
     * @param string $sessionId Session id.
     * @param int $volumeId Volume id.
     * @param string $directory Optional path to get index list on a subfolder.
     * @return array
     */
    public function prepareIndexList(string $sessionId, int $volumeId, string $directory = ''): array
    {
        try {
            $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);

            // Get the file list.
            $fileList = $this->getIndexListOnVolume($volume, $directory);

            // Remove the things we're not interested in indexing.
            $skippedItems = $this->extractSkippedItemsFromIndexList($fileList);
            $foldersFound = $this->extractFolderItemsFromIndexList($fileList);

            // Store the index list.
            $this->storeIndexList($fileList, $sessionId, $volumeId);

            foreach ($skippedItems as &$skippedItem) {
                $skippedItem = $volume->name . '/' . $skippedItem;
            }

            unset($skippedItem);

            $indexedFolderIds = [];
            $indexedFolderIds[Craft::$app->getVolumes()->ensureTopFolder($volume)->id] = true;

            // Ensure folders are in the DB
            $assets = Craft::$app->getAssets();
            foreach ($foldersFound as $fullPath) {
                $folder = $assets->ensureFolderByFullPathAndVolume(rtrim($fullPath, '/') . '/', $volume);
                $indexedFolderIds[$folder->id] = true;
            }

            // Compile a list of missing folders.
            $missingFolders = [];

            $folderCriteria = [
                'volumeId' => $volumeId,
            ];

            $allFolders = $assets->findFolders($folderCriteria);

            $normalizedDir = !empty($directory) ? rtrim($directory, '/') . '/' : '';

            foreach ($allFolders as $folderModel) {
                if (!isset($indexedFolderIds[$folderModel->id]) && $folderModel->path !== $normalizedDir && StringHelper::startsWith($folderModel->path, $normalizedDir)) {
                    $missingFolders[$folderModel->id] = $volume->name . '/' . $folderModel->path;
                }
            }

            return [
                'volumeId' => $volumeId,
                'total' => count($fileList),
                'missingFolders' => $missingFolders,
                'skippedFiles' => $skippedItems
            ];
        } catch (\Throwable $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return ['error' => $exception->getMessage()];
        }
    }

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
                if (strpos($segment, '_') === 0 && ($listing->getType() === 'dir' || $i < $lastSegmentIndex)) {
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
     */
    public function getExistingIndexingSessions(): array
    {
        $query = (new Query())
            ->select(['id', 'totalEntries', 'processedEntries', 'cacheRemoteImages', 'queueId', 'actionRequired', 'dateCreated', 'dateUpdated'])
            ->from(Table::ASSETINDEXINGSESSIONS);

        $rows = $query->all();

        $sessions = [];

        foreach ($rows as $row) {
            $sessions[] = new AssetIndexingSession($row);
        }

        return $sessions;
    }

    /**
     * Remove skipped items from an index list and return their paths.
     *
     * @param array $indexList Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @return array
     */
    public function extractSkippedItemsFromIndexList(array &$indexList): array
    {
        $isMysql = Craft::$app->getDb()->getIsMysql();
        $allowedExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;

        $skippedItems = array_filter($indexList, function($entry) use ($isMysql, $allowedExtensions) {
            if (preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $entry['basename'])) {
                return true;
            }

            if ($isMysql && StringHelper::containsMb4($entry['basename'])) {
                return true;
            }

            if (isset($entry['extension']) && !in_array(strtolower($entry['extension']), $allowedExtensions, true)) {
                return true;
            }

            return false;
        });

        $indexList = array_diff_key($indexList, $skippedItems);

        return array_keys($skippedItems);
    }

    /**
     * Remove folder items from an index list and return their paths.
     *
     * @param array $indexList Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @return array
     */
    public function extractFolderItemsFromIndexList(array &$indexList): array
    {
        $folderItems = array_filter($indexList, function($entry) {
            return $entry['type'] === 'dir';
        });

        $indexList = array_diff_key($indexList, $folderItems);

        return array_keys($folderItems);
    }

    /**
     * Store the index list in the index data table.
     *
     * @param array $indexList Index list generated by `AssetIndexer::getIndexListOnVolume()`
     * @param string $sessionId Session id.
     * @param int $volumeId Volume id.
     */
    public function storeIndexList(array $indexList, string $sessionId, int $volumeId)
    {
        $attributes = ['volumeId', 'sessionId', 'uri', 'size', 'timestamp', 'inProgress', 'completed'];
        $values = [];

        foreach ($indexList as $entry) {
            $values[] = [$volumeId, $sessionId, $entry['path'], $entry['size'], Db::prepareDateForDb(new \DateTime('@' . $entry['timestamp'])), false, false];
        }

        Db::batchInsert(Table::ASSETINDEXDATA, $attributes, $values);
    }

    /**
     * Process index for a volume.
     *
     * @param string $sessionId Session id.
     * @param int $volumeId Volume id.
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @return mixed
     */
    public function processIndexForVolume(string $sessionId, int $volumeId, bool $cacheImages = false)
    {
        $mutex = Craft::$app->getMutex();
        $lockName = 'idx--' . $sessionId;

        if (!$mutex->acquire($lockName, 5)) {
            throw new Exception('Could not acquire a lock for the indexing session "' . $sessionId . '".');
        }

        if (($indexEntryModel = $this->getNextIndexEntry($sessionId, $volumeId)) === null) {
            return false;
        }

        // Mark as started.
        $this->updateIndexEntry($indexEntryModel->id, ['inProgress' => true]);

        $mutex->release($lockName);

        try {
            $asset = $this->_indexFileByIndexData($indexEntryModel, true, $cacheImages);
            $this->updateIndexEntry($indexEntryModel->id, ['completed' => true, 'inProgress' => false, 'recordId' => $asset->id]);

            return ['result' => $asset->id];
        } catch (AssetDisallowedExtensionException $exception) {
            $this->updateIndexEntry($indexEntryModel->id, ['completed' => true, 'inProgress' => false]);
        }

        return ['result' => false];
    }

    /**
     * Returns the next item to index in an indexing session.
     *
     * @param string $sessionId Session id.
     * @param int $volumeId Volume id.
     * @return AssetIndexData|null
     */
    public function getNextIndexEntry(string $sessionId, int $volumeId)
    {
        $result = (new Query())
            ->select([
                'id',
                'volumeId',
                'sessionId',
                'uri',
                'size',
                'recordId',
                'timestamp',
                'completed',
                'inProgress',
            ])
            ->from([Table::ASSETINDEXDATA])
            ->where([
                'volumeId' => $volumeId,
                'sessionId' => $sessionId,
                'completed' => false,
                'inProgress' => false
            ])
            ->one();

        return $result ? new AssetIndexData($result) : null;
    }

    /**
     * Update indexing-process related data on an index entry.
     *
     * @param int $entryId Index entry id.
     * @param array $data Key=>value array of data to update.
     */
    public function updateIndexEntry(int $entryId, array $data)
    {
        // Only allow a few fields to be updated.
        $data = array_intersect_key($data, array_flip(['inProgress', 'completed', 'recordId']));
        Db::update(Table::ASSETINDEXDATA, $data, [
            'id' => $entryId,
        ]);
    }


    /**
     * Return a list of missing files for an indexing session.
     *
     * @param string $sessionId Session id.
     * @return array
     */
    public function getMissingFiles(string $sessionId): array
    {
        $output = [];

        // Load the record IDs of the files that were indexed.
        $processedFiles = (new Query())
            ->select(['recordId'])
            ->from([Table::ASSETINDEXDATA])
            ->where([
                'and',
                ['sessionId' => $sessionId],
                ['not', ['recordId' => null]]
            ])
            ->column();

        // Load the processed volume IDs for that session.
        $volumeIds = (new Query())
            ->select(['DISTINCT([[volumeId]])'])
            ->from([Table::ASSETINDEXDATA])
            ->where(['sessionId' => $sessionId])
            ->column();

        // What if there were no files at all?
        if (empty($volumeIds) && !Craft::$app->getRequest()->getIsConsoleRequest()) {
            $volumeIds = Craft::$app->getSession()->get('assetsVolumesBeingIndexed');
        }

        // Flip for faster lookup
        $processedFiles = array_flip($processedFiles);
        $assets = (new Query())
            ->select(['fi.volumeId', 'fi.id AS assetId', 'fi.filename', 'fo.path', 's.name AS volumeName'])
            ->from(['fi' => Table::ASSETS])
            ->innerJoin(['fo' => Table::VOLUMEFOLDERS], '[[fo.id]] = [[fi.folderId]]')
            ->innerJoin(['s' => Table::VOLUMES], '[[s.id]] = [[fi.volumeId]]')
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[fi.id]]')
            ->where(['fi.volumeId' => $volumeIds])
            ->andWhere(['e.dateDeleted' => null])
            ->all();

        foreach ($assets as $asset) {
            if (!isset($processedFiles[$asset['assetId']])) {
                $output[$asset['assetId']] = $asset['volumeName'] . '/' . $asset['path'] . $asset['filename'];
            }
        }

        return $output;
    }

    /**
     * Index a single file by Volume and path.
     *
     * @param VolumeInterface $volume
     * @param string $path
     * @param string $sessionId optional indexing session id.
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     * @return Asset
     * @throws AssetDisallowedExtensionException if attempting to index an Asset with a disallowed extension
     * @throws InvalidConfigException if misconfigured volume
     * @throws MissingAssetException if asset not found and `createIfMissing` set to `false`.
     * @throws VolumeException if unable to read metadata.
     */
    public function indexFile(VolumeInterface $volume, string $path, string $sessionId = '', bool $cacheImages = false, bool $createIfMissing = true): Asset
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
     * @param string $sessionId
     * @param bool $cacheImages
     * @param bool $createIfMissing
     * @return Asset
     * @throws AssetDisallowedExtensionException if attempting to index an Asset with a disallowed extension
     * @throws InvalidConfigException if misconfigured volume
     * @throws MissingAssetException if asset not found and `createIfMissing` set to `false`.
     */
    public function indexFileByListing(VolumeListing $listing, string $sessionId = '', bool $cacheImages = false, bool $createIfMissing = true): Asset
    {
        $volume = $listing->getVolume();
        $indexEntry = new AssetIndexData([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId ?: $this->getIndexingSessionId(),
            'uri' => $listing->getUri(),
            'size' => $listing->getFileSize(),
            'timestamp' => $listing->getDateModified(),
            'inProgress' => true,
            'completed' => false
        ]);

        return $this->indexFileByEntry($indexEntry, $cacheImages, $createIfMissing);
    }

    /**
     * Indexes a file by its index entry.
     *
     * @param AssetIndexData $indexEntry
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @param bool $createIfMissing Whether the asset record should be created if it doesn't exist yet
     * @return bool|Asset
     * @throws AssetDisallowedExtensionException If the file being indexed has a disallowed extension
     * @throws InvalidConfigException
     * @throws MissingAssetException if the asset record doesn't exist and $createIfMissing is false
     */
    public function indexFileByEntry(AssetIndexData $indexEntry, bool $cacheImages = false, bool $createIfMissing = true)
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
            $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false]);
            throw $exception;
        }

        return $asset;
    }

    /**
     * Clean up stale asset indexing data. Stale indexing data is all session data for sessions that have all the recordIds set.
     *
     * @throws \yii\db\Exception
     */
    public function deleteStaleIndexingData()
    {
        // Clean up stale indexing data (all sessions that have all recordIds set)
        $sessionsInProgress = (new Query())
            ->select(['sessionId'])
            ->from([Table::ASSETINDEXDATA])
            ->where(['completed' => false])
            ->groupBy(['sessionId'])
            ->column();

        $db = Craft::$app->getDb();

        if (empty($sessionsInProgress)) {
            $db->createCommand()
                ->delete(Table::ASSETINDEXDATA)
                ->execute();
        } else {
            $db->createCommand()
                ->delete(
                    Table::ASSETINDEXDATA,
                    ['not', ['sessionId' => $sessionsInProgress]])
                ->execute();
        }
    }

    /**
     * Indexes a file.
     *
     * @param AssetIndexData $indexEntry Asset Index Data entry that contains information for the Asset-to-be.
     * @param bool $createIfMissing Whether the asset record should be created if none exists
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @return Asset
     * @throws AssetDisallowedExtensionException if the extension of the file is not allowed.
     * @throws InvalidConfigException
     * @throws MissingAssetException
     */
    private function _indexFileByIndexData(AssetIndexData $indexEntry, bool $createIfMissing = true, bool $cacheImages)
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
}
