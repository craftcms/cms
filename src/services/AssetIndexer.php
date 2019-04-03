<?php

namespace craft\services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\AssetDisallowedExtensionException;
use craft\errors\AssetException;
use craft\errors\AssetLogicException;
use craft\errors\VolumeObjectNotFoundException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\models\AssetIndexData;
use craft\records\AssetIndexData as AssetIndexDataRecord;
use yii\base\Component;

/**
 * Class AssetIndexer
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license http://craftcms.com/license Craft License Agreement
 * @see http://craftcms.com
 * @package craft.app.services
 * @since 3.0
 */
class AssetIndexer extends Component
{
    // Public Methods
    // =========================================================================

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
            /**
             * @var Volume $volume
             */
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
            $indexedFolderIds[Craft::$app->getVolumes()->ensureTopFolder($volume)] = true;

            // Ensure folders are in the DB
            $assets = Craft::$app->getAssets();
            foreach ($foldersFound as $fullPath) {
                $folderId = $assets->ensureFolderByFullPathAndVolume(rtrim($fullPath, '/') . '/', $volume);
                $indexedFolderIds[$folderId] = true;
            }

            // Compile a list of missing folders.
            $missingFolders = [];

            $allFolders = $assets->findFolders(
                [
                    'volumeId' => $volumeId
                ]);

            foreach ($allFolders as $folderModel) {
                if (!isset($indexedFolderIds[$folderModel->id])) {
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
     * Get a sorted list of files on a volume by it's id and an optional directory filter indexed by path.
     *
     * @param VolumeInterface $volume The Volume to perform indexing on.
     * @param string $directory Optional path to get index list on a subfolder.
     * @return array
     */
    public function getIndexListOnVolume(VolumeInterface $volume, string $directory = ''): array
    {

        $fileList = $volume->getFileList($directory, true);

        $fileList = array_filter(
            $fileList,
            function($value) {
                $path = $value['path'];
                $segments = explode('/', $path);

                $segmentCount = count($segments);

                for ($segmentIndex = 0; $segmentIndex < $segmentCount; $segmentIndex++) {
                    $currentSegment = $segments[$segmentIndex];

                    // Skip if segment begins with an underscrore and (this is a directory or not the last segment)
                    if ($currentSegment[0] === '_' && ($value['type'] === 'dir' || $segmentIndex + 1 < $segmentCount)) {
                        return false;
                    }
                }

                return true;
            }
        );

        // Sort by number of slashes to ensure that parent folders are listed earlier than their children
        uasort(
            $fileList,
            function($a, $b) {
                $a = substr_count($a['path'], '/');
                $b = substr_count($b['path'], '/');

                if ($a === $b) {
                    return 0;
                }

                return $a < $b ? -1 : 1;
            }
        );

        return $fileList;
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

        $skippedItems = array_filter($indexList, function($entry) use ($isMysql) {
            if (preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $entry['basename'])) {
                return true;
            }
            if ($isMysql && StringHelper::containsMb4($entry['basename'])) {
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

        Craft::$app->getDb()->createCommand()
            ->batchInsert(
                Table::ASSETINDEXDATA,
                $attributes,
                $values)
            ->execute();
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
            $asset = $this->_indexFileByIndexData($indexEntryModel, $cacheImages);
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

        Craft::$app->getDb()->createCommand()
            ->update(
                Table::ASSETINDEXDATA,
                $data,
                ['id' => $entryId])
            ->execute();
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

        // Load the processed volume IDs for that sessions.
        $volumeIds = (new Query())
            ->select(['DISTINCT([[volumeId]])'])
            ->from([Table::ASSETINDEXDATA])
            ->where(['sessionId' => $sessionId])
            ->column();

        // What if there were no files at all?
        if (empty($volumeIds)) {
            $volumeIds = Craft::$app->getSession()->get('assetsVolumesBeingIndexed');
        }

        // Flip for faster lookup
        $processedFiles = array_flip($processedFiles);
        $assets = (new Query())
            ->select(['fi.volumeId', 'fi.id AS assetId', 'fi.filename', 'fo.path', 's.name AS volumeName'])
            ->from(['{{%assets}} fi'])
            ->innerJoin('{{%volumefolders}} fo', '[[fi.folderId]] = [[fo.id]]')
            ->innerJoin('{{%volumes}} s', '[[s.id]] = [[fi.volumeId]]')
            ->innerJoin('{{%elements}} e', '[[e.id]] = [[fi.id]]')
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
     * @param Volume $volume
     * @param string $path
     * @param string $sessionId optional indexing session id.
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @throws VolumeObjectNotFoundException If the file to be indexed cannot be found.
     * @return bool|Asset
     */
    public function indexFile(Volume $volume, string $path, string $sessionId = '', bool $cacheImages = false)
    {

        $fileInfo = $volume->getFileMetadata($path);
        $folderPath = dirname($path);

        if ($folderPath !== '.') {
            Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($folderPath . '/', $volume);
        }

        $indexEntry = new AssetIndexData([
            'volumeId' => $volume->id,
            'sessionId' => $sessionId ?: $this->getIndexingSessionId(),
            'uri' => $path,
            'size' => $fileInfo['size'],
            'timestamp' => $fileInfo['timestamp'],
            'inProgress' => true,
            'completed' => false
        ]);

        $recordData = $indexEntry->toArray();

        // For some reason Postgres chokes if we don't do that.
        unset($recordData['id']);

        $record = new AssetIndexDataRecord($recordData);
        $record->save();

        $indexEntry->id = $record->id;

        $asset = $this->_indexFileByIndexData($indexEntry, $cacheImages);
        $this->updateIndexEntry($indexEntry->id, ['completed' => true, 'inProgress' => false, 'recordId' => $asset->id]);

        return $asset;
    }

    // Private Methods
    // =========================================================================

    /**
     * Indexes a file.
     *
     * @param AssetIndexData $indexEntryModel Asset Index Data entry that contains information for the Asset-to-be.
     * @param bool $cacheImages Whether remotely-stored images should be downloaded and stored locally, to speed up transform generation.
     * @return Asset
     * @throws AssetDisallowedExtensionException if the extension of the file is not allowed.
     * @throws AssetLogicException if trying to index a file in a folder that does not exist.
     */
    private function _indexFileByIndexData(AssetIndexData $indexEntryModel, bool $cacheImages)
    {
        // Determine the parent folder
        $uriPath = $indexEntryModel->uri;
        $dirname = dirname($uriPath);

        if ($dirname === '.') {
            $parentId = ':empty:';
            $path = '';
        } else {
            $parentId = false;
            $path = $dirname . '/';
        }

        $assets = Craft::$app->getAssets();
        $folder = $assets->findFolder(
            [
                'volumeId' => $indexEntryModel->volumeId,
                'path' => $path,
                'parentId' => $parentId
            ]);

        if (!$folder) {
            throw new AssetLogicException("The folder {$path} does not exist");
        }

        /** @var Volume $volume */
        $volume = $folder->getVolume();

        // Check if the extension is allowed
        $extension = pathinfo($indexEntryModel->uri, PATHINFO_EXTENSION);
        $filename = basename($indexEntryModel->uri);

        if (!in_array(strtolower($extension), Craft::$app->getConfig()->getGeneral()->allowedFileExtensions, true)) {
            throw new AssetDisallowedExtensionException("File “{$indexEntryModel->uri}” was not indexed because extension “{$extension}” is not allowed.");
        }

        $folderId = $folder->id;

        /**
         * @var Asset $asset
         */
        $asset = Asset::find()
            ->filename(Db::escapeParam($filename))
            ->folderId($folderId)
            ->one();

        // Create an Asset if there is none.
        if (!$asset) {
            $asset = new Asset();
            $asset->volumeId = $volume->id;
            $asset->folderId = $folderId;
            $asset->folderPath = $folder->path;
            $asset->filename = $filename;
            $asset->kind = AssetsHelper::getFileKindByExtension($filename);
        }


        $asset->size = $indexEntryModel->size;
        $timeModified = $indexEntryModel->timestamp;

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
                        } catch (AssetException $e) {
                            Craft::info($e->getMessage());
                        }
                    }

                    // if $dimensions is not an array by now, either smart-guessing failed or the user wants to cache this.
                    if (!is_array($dimensions)) {
                        $tempPath = AssetsHelper::tempFilePath(pathinfo($filename, PATHINFO_EXTENSION));
                        $volume->saveFileLocally($indexEntryModel->uri, $tempPath);
                        $dimensions = Image::imageSize($tempPath);
                    }
                }

                list ($w, $h) = $dimensions;
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
