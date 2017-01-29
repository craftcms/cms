<?php
namespace craft\services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\elements\Asset;
use craft\errors\VolumeObjectNotFoundException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\models\AssetIndexData;
use craft\records\AssetIndexData as AssetIndexDataRecord;
use DateTime;
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
    public function getIndexingSessionId(): string
    {
        return StringHelper::UUID();
    }

    /**
     * Gets the index list for a volume.
     *
     * @param string $sessionId
     * @param int    $volumeId
     * @param string $directory
     *
     * @return array
     */
    public function prepareIndexList(string $sessionId, int $volumeId, string $directory = ''): array
    {
        try {
            /** @var Volume $volume */
            $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);

            $fileList = $volume->getFileList($directory, true);

            $fileList = array_filter(
                $fileList,
                function($value) {
                    $path = $value['path'];
                    $segments = explode('/', $path);

                    foreach ($segments as $segment) {
                        if (isset($segment[0]) && $segment[0] === '_') {
                            return false;
                        }
                    }

                    return true;
                }
            );

            // Sort by number of slashes to ensure that parent folders are listed earlier than their children
            usort(
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
                    if ($file['type'] === 'dir') {
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
     * @param string $sessionId
     * @param int    $offset
     * @param int    $volumeId
     *
     * @return mixed
     */
    public function processIndexForVolume(string $sessionId, int $offset, int $volumeId)
    {
        $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);

        if (($indexEntryModel = $this->getIndexEntry($volumeId, $sessionId, $offset)) === null) {
            return false;
        }

        $uriPath = $indexEntryModel->uri;
        $asset = $this->_indexFile($volume, $uriPath);

        if ($asset) {
            $this->updateIndexEntryRecordId($indexEntryModel->id, $asset->id);

            $asset->size = $indexEntryModel->size;
            $timeModified = $indexEntryModel->timestamp;

            if ($asset->kind === 'image') {
                $targetPath = $asset->getImageTransformSourcePath();

                if ($asset->dateModified != $timeModified || !is_file($targetPath)) {
                    if (!$volume instanceof LocalVolumeInterface) {
                        $volume->saveFileLocally($uriPath, $targetPath);

                        // Store the local source for now and set it up for deleting, if needed
                        $assetTransforms = Craft::$app->getAssetTransforms();
                        $assetTransforms->storeLocalSource(
                            $targetPath
                        );
                        $assetTransforms->queueSourceForDeletingIfNecessary($targetPath);
                    }

                    clearstatcache();
                    list ($asset->width, $asset->height) = Image::imageSize(
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
     * @param array $data
     *
     * @return void
     */
    public function storeIndexEntry(array $data)
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
     * @param int    $volumeId
     * @param string $sessionId
     * @param int    $offset
     *
     * @return AssetIndexData|null
     */
    public function getIndexEntry(int $volumeId, string $sessionId, int $offset)
    {
        $record = AssetIndexDataRecord::findOne(
            [
                'volumeId' => $volumeId,
                'sessionId' => $sessionId,
                'offset' => $offset
            ]
        );

        if (!$record) {
            return null;
        }

        return new AssetIndexData($record->toArray([
            'id',
            'volumeId',
            'sessionId',
            'offset',
            'uri',
            'size',
            'recordId',
            'timestamp',
        ]));
    }

    /**
     * @param int $entryId
     * @param int $recordId
     *
     * @return void
     */
    public function updateIndexEntryRecordId(int $entryId, int $recordId)
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
     * @param array  $volumeIds
     * @param string $sessionId
     *
     * @return array
     */
    public function getMissingFiles(array $volumeIds, string $sessionId): array
    {
        $output = [];

        // Load the record IDs of the files that were indexed.
        $processedFiles = (new Query())
            ->select(['recordId'])
            ->from(['{{%assetindexdata}}'])
            ->where([
                'and',
                ['sessionId' => $sessionId],
                ['not', ['recordId' => null]]
            ])
            ->column();

        // Flip for faster lookup
        $processedFiles = array_flip($processedFiles);
        $assets = (new Query())
            ->select(['fi.volumeId', 'fi.id AS assetId', 'fi.filename', 'fo.path', 's.name AS volumeName'])
            ->from(['{{%assets}} fi'])
            ->innerJoin('{{%volumefolders}} fo', '[[fi.folderId]] = [[fo.id]]')
            ->innerJoin('{{%volumes}} s', '[[s.id]] = [[fi.volumeId]]')
            ->where(['fi.volumeId' => $volumeIds])
            ->all();

        foreach ($assets as $asset) {
            if (!isset($processedFiles[$asset['assetId']])) {
                $output[$asset['assetId']] = $asset['volumeName'].'/'.$asset['path'].$asset['filename'];
            }
        }

        return $output;
    }

    /**
     * Index a single file by Volume and path.
     *
     * @param VolumeInterface $volume
     * @param  string         $path
     * @param bool            $checkIfExists
     *
     * @throws VolumeObjectNotFoundException If the file to be indexed cannot be found.
     * @return bool|Asset
     */
    public function indexFile(VolumeInterface $volume, string $path, bool $checkIfExists = true)
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
     * @param VolumeInterface $volume  The volume.
     * @param string          $uriPath The URI path fo the file to index.
     *
     * @return Asset|bool
     */
    private function _indexFile(VolumeInterface $volume, string $uriPath)
    {
        /** @var Volume $volume */
        $extension = pathinfo($uriPath, PATHINFO_EXTENSION);

        if (Craft::$app->getConfig()->isExtensionAllowed($extension)) {
            $parts = explode('/', $uriPath);
            $filename = array_pop($parts);

            $searchFullPath = implode('/', $parts).(empty($parts) ? '' : '/');

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

            $assetModel = Asset::find()
                ->filename(Db::escapeParam($filename))
                ->folderId($folderId)
                ->one();

            if ($assetModel === null) {
                $assetModel = new Asset();
                $assetModel->volumeId = $volume->id;
                $assetModel->folderId = $folderId;
                $assetModel->filename = $filename;
                $assetModel->kind = AssetsHelper::getFileKindByExtension($uriPath);
                $assetModel->indexInProgress = true;
                $assets->saveAsset($assetModel);
            }

            return $assetModel;
        }

        return false;
    }
}
