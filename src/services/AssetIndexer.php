<?php
namespace craft\app\services;

use Craft;
use craft\app\base\Volume;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\errors\VolumeObjectNotFoundException;
use craft\app\helpers\Assets as AssetsHelper;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;
use craft\app\models\AssetIndexData;
use craft\app\records\AssetIndexData as AssetIndexDataRecord;
use yii\base\Component;

/**
 * Class AssetIndexer
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.services
 * @since      3.0
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
    public function getIndexingSessionId()
    {
        return StringHelper::UUID();
    }

    /**
     * Gets the index list for a volume.
     *
     * @param $sessionId
     * @param $volumeId
     * @param $directory
     *
     * @return array
     */
    public function prepareIndexList($sessionId, $volumeId, $directory = '')
    {
        try {
            $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);

            $fileList = $volume->getFileList($directory);

            $fileList = array_filter(
                $fileList,
                function ($value) {
                    $path = $value['path'];
                    $segments = explode('/', $path);

                    foreach ($segments as $segment) {
                        if (isset($segment[0]) && $segment[0] == '_') {
                            return false;
                        }
                    }

                    return true;
                }
            );

            // Sort by number of slashes to ensure that parent folders are listed earlier than their children
            usort(
                $fileList,
                function ($a, $b) {
                    $a = substr_count($a['path'], '/');
                    $b = substr_count($b['path'], '/');

                    return ($a == $b ? 0 : ($a < $b ? -1 : 1));
                }
            );

            $bucketFolders = [];
            $skippedFiles = [];
            $offset = 0;
            $total = 0;

            foreach ($fileList as $file) {
                $allowedByFilter = !preg_match(
                    AssetsHelper::INDEX_SKIP_ITEMS_PATTERN,
                    $file['basename']
                );

                if ($allowedByFilter) {
                    if ($file['type'] == 'dir') {
                        $bucketFolders[$file['path']] = true;
                    } else {
                        $indexEntry = [
                            'volumeId' => $volumeId,
                            'sessionId' => $sessionId,
                            'offset' => $offset++,
                            'uri' => $file['path'],
                            'size' => $file['size'],
                            'timestamp' => $file['timestamp']
                        ];

                        $this->storeIndexEntry($indexEntry);
                        $total++;
                    }
                } else {
                    $skippedFiles[] = $volume->name.'/'.$file['path'];
                }
            }

            $indexedFolderIds = [];
            $indexedFolderIds[Craft::$app->getVolumes()->ensureTopFolder($volume)] = true;

            // Ensure folders are in the DB
            $assets = Craft::$app->getAssets();
            foreach ($bucketFolders as $fullPath => $nothing) {
                $folderId = $assets->ensureFolderByFullPathAndVolumeId(
                    rtrim(
                        $fullPath,
                        '/'
                    ).'/',
                    $volumeId
                );
                $indexedFolderIds[$folderId] = true;
            }

            $missingFolders = [];

            $allFolders = $assets->findFolders(
                [
                    'volumeId' => $volumeId
                ]);

            foreach ($allFolders as $folderModel) {
                if (!isset($indexedFolderIds[$folderModel->id])) {
                    $missingFolders[$folderModel->id] = $volume->name.'/'.$folderModel->path;
                }
            }

            return [
                'volumeId' => $volumeId,
                'total' => $total,
                'missingFolders' => $missingFolders,
                'skippedFiles' => $skippedFiles
            ];
        } catch (\Exception $exception) {
            return ['error' => $exception->getMessage()];
        }
    }

    /**
     * Process index for a volume.
     *
     * @param $sessionId
     * @param $offset
     * @param $volumeId
     *
     * @return mixed
     */
    public function processIndexForVolume($sessionId, $offset, $volumeId)
    {
        $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);

        $indexEntryModel = $this->getIndexEntry($volumeId, $sessionId, $offset);

        if (empty($indexEntryModel)) {
            return false;
        }

        $uriPath = $indexEntryModel->uri;
        $asset = $this->_indexFile($volume, $uriPath);

        if ($asset) {
            $this->updateIndexEntryRecordId($indexEntryModel->id, $asset->id);

            $asset->size = $indexEntryModel->size;
            $timeModified = new DateTime($indexEntryModel->timestamp);

            if ($asset->kind == 'image') {
                $targetPath = $asset->getImageTransformSourcePath();

                if ($asset->dateModified != $timeModified || !Io::fileExists(
                        $targetPath
                    )
                ) {
                    if (!$volume::isLocal()) {
                        $volume->saveFileLocally($uriPath, $targetPath);

                        // Store the local source for now and set it up for deleting, if needed
                        $assetTransforms = Craft::$app->getAssetTransforms();
                        $assetTransforms->storeLocalSource(
                            $targetPath
                        );
                        $assetTransforms->queueSourceForDeletingIfNecessary($targetPath);
                    }

                    clearstatcache();
                    list ($asset->width, $asset->height) = Image::getImageSize(
                        $targetPath
                    );
                }
            }

            $asset->dateModified = $timeModified;

            Craft::$app->getAssets()->saveAsset($asset);

            return ['result' => $asset->id];
        }

        return ['result' => false];
    }

    /**
     * Store an index entry.
     *
     * @param $data
     */
    public function storeIndexEntry($data)
    {
        $entry = new AssetIndexDataRecord();

        foreach ($data as $key => $value) {
            $entry->setAttribute($key, $value);
        }

        $entry->save();
    }

    /**
     * Return an index model.
     *
     * @param $volumeId
     * @param $sessionId
     * @param $offset
     *
     * @return AssetIndexData|bool
     */
    public function getIndexEntry($volumeId, $sessionId, $offset)
    {
        $record = AssetIndexDataRecord::findOne(
            [
                'volumeId' => $volumeId,
                'sessionId' => $sessionId,
                'offset' => $offset
            ]
        );

        if ($record) {
            return AssetIndexData::create($record);
        }

        return false;
    }

    /**
     * @param $entryId
     * @param $recordId
     *
     * @return void
     */
    public function updateIndexEntryRecordId($entryId, $recordId)
    {
        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%assetindexdata}}',
                ['recordId' => $recordId],
                ['id' => $entryId])
            ->execute();
    }


    /**
     * Return a list of missing files for an indexing session.
     *
     * @param $volumeIds
     * @param $sessionId
     *
     * @return array
     */
    public function getMissingFiles($volumeIds, $sessionId)
    {
        $output = [];

        // Load the record IDs of the files that were indexed.
        $processedFiles = (new Query())
            ->select('recordId')
            ->from('{{%assetindexdata}}')
            ->where(
                [
                    'and',
                    ['sessionId' => $sessionId],
                    'recordId is not null'
                ],
                [':sessionId' => $sessionId])
            ->column();

        // Flip for faster lookup
        $processedFiles = array_flip($processedFiles);

        $fileEntries = (new Query())
            ->select(
                'fi.volumeId, fi.id AS fileId, fi.filename, fo.path, s.name AS volumeName'
            )
            ->from('{{%assets}} AS fi')
            ->innerJoin('{{%volumefolders}} AS fo', 'fi.folderId = fo.id')
            ->innerJoin('{{%volumes}} AS s', 's.id = fi.volumeId')
            ->where(['in', 'fi.volumeId', $volumeIds])
            ->all();

        foreach ($fileEntries as $fileEntry) {
            if (!isset($processedFiles[$fileEntry['fileId']])) {
                $output[$fileEntry['fileId']] = $fileEntry['volumeName'].'/'.$fileEntry['path'].$fileEntry['filename'];
            }
        }

        return $output;
    }

    /**
     * Index a single file by Volume and path.
     *
     * @param Volume  $volume
     * @param         $path
     * @param boolean $checkIfExists
     *
     * @throws VolumeObjectNotFoundException If the file to be indexed cannot be found.
     * @return boolean|Asset
     */
    public function indexFile(Volume $volume, $path, $checkIfExists = true)
    {
        if ($checkIfExists && !$volume->fileExists($path)) {
            throw new VolumeObjectNotFoundException(Craft::t(
                'app',
                'File was not found while attempting to index {path}!',
                ['path' => $path]
            ));
        }

        return $this->_indexFile($volume, $path);
    }

    // Private Methods
    // =========================================================================

    /**
     * Indexes a file.
     *
     * @param Volume $volume  The volume.
     * @param string $uriPath The URI path fo the file to index.
     *
     * @return Asset|bool
     */
    private function _indexFile($volume, $uriPath)
    {
        $extension = Io::getExtension($uriPath);

        if (Io::isExtensionAllowed($extension)) {
            $parts = explode('/', $uriPath);
            $filename = array_pop($parts);

            $searchFullPath = join('/', $parts).(empty($parts) ? '' : '/');

            if (empty($searchFullPath)) {
                $parentId = ':empty:';
            } else {
                $parentId = false;
            }

            $assets = Craft::$app->getAssets();
            $parentFolder = $assets->findFolder(
                [
                    'volumeId' => $volume->id,
                    'path' => $searchFullPath,
                    'parentId' => $parentId
                ]);

            if (empty($parentFolder)) {
                return false;
            }

            $folderId = $parentFolder->id;

            $assetModel = $assets->findAsset(
                [
                    'folderId' => $folderId,
                    'filename' => $filename
                ]);

            if (is_null($assetModel)) {
                $assetModel = new Asset();
                $assetModel->volumeId = $volume->id;
                $assetModel->folderId = $folderId;
                $assetModel->filename = $filename;
                $assetModel->kind = Io::getFileKind($extension);
                $assetModel->indexInProgress = true;
                $assets->saveAsset($assetModel);
            }

            return $assetModel;
        }

        return false;
    }
}
