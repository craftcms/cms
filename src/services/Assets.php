<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\elements\db\AssetQuery;
use craft\app\elements\User;
use craft\app\errors\ActionCancelledException;
use craft\app\errors\AssetConflictException;
use craft\app\errors\AssetDisallowedExtensionException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\AssetMissingException;
use craft\app\errors\UploadFailedException;
use craft\app\errors\VolumeException;
use craft\app\errors\VolumeObjectExistsException;
use craft\app\errors\VolumeObjectNotFoundException;
use craft\app\errors\ElementSaveException;
use craft\app\errors\Exception;
use craft\app\errors\FileException;
use craft\app\errors\ValidationException;
use craft\app\events\AssetEvent;
use craft\app\events\ReplaceAssetEvent;
use craft\app\helpers\Assets as AssetsHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Db;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;
use craft\app\helpers\Url;
use craft\app\models\AssetTransformIndex;
use craft\app\models\VolumeFolder as VolumeFolderModel;
use craft\app\models\FolderCriteria;
use craft\app\models\VolumeFolder;
use craft\app\records\Asset as AssetRecord;
use craft\app\records\VolumeFolder as VolumeFolderRecord;
use craft\app\volumes\Temp;
use yii\base\Component;

/**
 * Class Assets service.
 *
 * An instance of the Assets service is globally accessible in Craft via [[Application::assets `Craft::$app->getAssets()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Assets extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event AssetEvent The event that is triggered before an asset is uploaded.
     *
     * You may set [[AssetEvent::isValid]] to `false` to prevent the asset from getting saved.
     */
    const EVENT_BEFORE_UPLOAD_ASSET = 'beforeUploadAsset';

    /**
     * @event AssetEvent The event that is triggered before an asset is saved.
     */
    const EVENT_BEFORE_SAVE_ASSET = 'beforeSaveAsset';

    /**
     * @event AssetEvent The event that is triggered after an asset is saved.
     */
    const EVENT_AFTER_SAVE_ASSET = 'afterSaveAsset';

    /**
     * @event AssetEvent The event that is triggered before an asset is replaced.
     */
    const EVENT_BEFORE_REPLACE_ASSET = 'beforeReplaceFile';

    /**
     * @event AssetEvent The event that is triggered after an asset is replaced.
     */
    const EVENT_AFTER_REPLACE_ASSET = 'afterReplaceFile';

    /**
     * @event AssetEvent The event that is triggered before an asset is deleted.
     */
    const EVENT_BEFORE_DELETE_ASSET = 'beforeDeleteAsset';

    /**
     * @event AssetEvent The event that is triggered after an asset is deleted.
     */
    const EVENT_AFTER_DELETE_ASSET = 'afterDeleteAsset';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_foldersById;

    // Public Methods
    // =========================================================================

    /**
     * Returns all top-level files in a volume.
     *
     * @param integer $volumeId
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getFilesByVolumeId($volumeId, $indexBy = null)
    {
        return Asset::find()
            ->volumeId($volumeId)
            ->indexBy($indexBy)
            ->all();
    }

    /**
     * Returns a file by its ID.
     *
     * @param             $assetId
     * @param string|null $localeId
     *
     * @return Asset|null
     */
    public function getAssetById($assetId, $localeId = null)
    {
        return Craft::$app->getElements()->getElementById($assetId,
            Asset::className(), $localeId);
    }

    /**
     * Finds the first file that matches the given criteria.
     *
     * @param mixed $criteria
     *
     * @return Asset|null
     */
    public function findAsset($criteria = null)
    {
        $query = $this->_createAssetQuery($criteria);

        return $query->one();
    }

    /**
     * Finds all assets that matches the given criteria.
     *
     * @param mixed $criteria
     *
     * @return array|null
     */
    public function findAssets($criteria = null)
    {
        $query = $this->_createAssetQuery($criteria);

        return $query->all();
    }

    /**
     * Gets the total number of assets that match a given criteria.
     *
     * @param mixed $criteria
     *
     * @return integer
     */
    public function getTotalAssets($criteria = null)
    {
        if ($criteria instanceof AssetQuery) {
            $query = $criteria;
        } else {
            $query = Asset::find()->configure($criteria);
        }

        return $query->count();
    }

    /**
     * Save an Asset.
     *
     * Saves an Asset. If the 'newFilePath' property is set, will replace the existing file.
     * For new assets, this property MUST bet set.
     *
     * @param Asset $asset
     *
     * @throws AssetDisallowedExtensionException If the file extension is not allowed.
     * @throws FileException                     If there was a problem with the actual file.
     * @throws AssetConflictException            If a file with such name already exists.
     * @throws AssetLogicException               If it's a new Asset and there's no file or there's no folder id set.
     * @throws VolumeObjectExistsException       If the file actually exists on the volume, but on in the index.
     * @throws UploadFailedException             If for some reason it's not possible to write the file to the final location
     * @return void
     */
    public function saveAsset(Asset $asset)
    {
        $isNew = empty($asset->id);

        if ($isNew && empty($asset->newFilePath) && empty($asset->indexInProgress)) {
            throw new AssetLogicException(Craft::t('app',
                'A new Asset cannot be created without a file.'));
        }

        if (empty($asset->folderId)) {
            throw new AssetLogicException(Craft::t('app',
                'All Assets must have folder ID set.'));
        }

        $extension = $asset->getExtension();

        if (!Io::isExtensionAllowed($extension)) {
            throw new AssetDisallowedExtensionException(Craft::t('app',
                'The extension “{extension}” is not allowed.',
                ['extension' => $extension]));
        }

        $existingAsset = $this->findAsset([
            'filename' => $asset->filename,
            'folderId' => $asset->folderId
        ]);

        if ($existingAsset && $existingAsset->id != $asset->id) {
            throw new AssetConflictException(Craft::t('app',
                'A file with the name “{filename}” already exists in the folder.',
                ['filename' => $asset->filename]));
        }

        $volume = $asset->getVolume();

        if (!$volume) {
            throw new AssetLogicException(Craft::t('app',
                'Volume does not exist with the id of {id}.',
                ['id' => $asset->volumeId]));
        }

        if (!empty($asset->newFilePath)) {
            if (Io::getFileKind(Io::getExtension($asset->newFilePath)) == 'image') {
                Image::cleanImageByPath($asset->newFilePath);
            }

            $stream = fopen($asset->newFilePath, 'r');

            if (!$stream) {
                throw new FileException(Craft::t('app',
                    'Could not open file for streaming at {path}',
                    ['path' => $asset->newFilePath]));
            }

            $uriPath = $asset->getUri();

            $event = new AssetEvent(['asset' => $asset]);
            $this->trigger(static::EVENT_BEFORE_UPLOAD_ASSET, $event);


            // Explicitly re-throw VolumeFileExistsException
            try {
                $result = $volume->createFileByStream($uriPath, $stream);
            } catch (VolumeObjectExistsException $exception) {
                // Replace the file if this is the temporary Volume.
                if (is_null($asset->volumeId)) {
                    $volume->deleteFile($uriPath);
                    $result = $volume->createFileByStream($uriPath, $stream);
                } else {
                    throw $exception;
                }
            }

            if (!$result) {
                throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            $asset->dateModified = new DateTime();
            $asset->size = Io::getFileSize($asset->newFilePath);
            $asset->kind = Io::getFileKind($asset->getExtension());

            if ($asset->kind == 'image' && !empty($asset->newFilePath)) {

                list ($asset->width, $asset->height) = Image::getImageSize($asset->newFilePath);
            }
        }

        $this->_storeAssetRecord($asset);

        // Now that we have an ID, store the source
        if (!$volume->isLocal() && $asset->kind == 'image' && !empty($asset->newFilePath)) {
            // Store the local source for now and set it up for deleting, if needed
            $assetTransforms = Craft::$app->getAssetTransforms();
            $assetTransforms->storeLocalSource($asset->newFilePath,
                $asset->getImageTransformSourcePath());
            $assetTransforms->queueSourceForDeletingIfNecessary($asset->getImageTransformSourcePath());
        }
    }

    /**
     * Replaces an Asset with another.
     *
     * @param Asset $assetToReplace
     * @param Asset $assetToReplaceWith
     * @param boolean $mergeAssets whether to replace content as well.
     *
     * @return void
     */
    public function replaceAsset(
        Asset $assetToReplace,
        Asset $assetToReplaceWith,
        $mergeAssets = false
    ) {
        $targetVolume = $assetToReplace->getVolume();

        // TODO purge cached files for remote Volumes.

        // Clear all thumb and transform data
        if (Image::isImageManipulatable($assetToReplace->getExtension())) {
            Craft::$app->getAssetTransforms()->deleteAllTransformData($assetToReplace);
        }

        // Handle things differently depending on whether that's an upload or an asset move.
        if ($mergeAssets) {
            Craft::$app->getElements()->mergeElementsByIds($assetToReplace->id,
                $assetToReplaceWith->id);

            // Replace the asset - delete the conflicting asset and move the asset in it's place.
            $targetVolume->deleteFile($assetToReplace->getUri());
            $this->_moveAssetToFolder($assetToReplaceWith,
                $assetToReplace->getFolder());

            $assetToReplaceWith->folderId = $assetToReplace->folderId;
            $assetToReplaceWith->volumeId = $assetToReplace->volumeId;
            $assetToReplaceWith->filename = $assetToReplace->filename;
            $this->saveAsset($assetToReplaceWith);
        } else {
            // Update the attributes and save the Asset
            $assetToReplace->dateModified = $assetToReplaceWith->dateModified;
            $assetToReplace->size = $assetToReplaceWith->size;
            $assetToReplace->kind = $assetToReplaceWith->kind;
            $assetToReplace->width = $assetToReplaceWith->width;
            $assetToReplace->height = $assetToReplaceWith->height;

            // Replace the asset - delete the conflicting asset and move the asset in it's place.
            $targetVolume->deleteFile($assetToReplace->getUri());
            $this->_moveAssetToFolder($assetToReplaceWith,
                $assetToReplace->getFolder(), $assetToReplace->filename);

            $this->saveAsset($assetToReplace);
            $this->deleteAssetsByIds($assetToReplaceWith->id, false);
        }
    }

    /**
     * Replace an Asset's file.
     *
     * Replace an Asset's file by it's id, a local file and the filename to use.
     *
     * @param $assetId
     * @param $pathOnServer
     * @param $filename
     *
     *
     * @throws ActionCancelledException If something prevented the Asset replacement via Event.
     * @throws FileException            If there was a problem with the actual file.
     * @throws AssetLogicException      If the Asset to be replaced cannot be found.
     * @return void
     */
    public function replaceAssetFile($assetId, $pathOnServer, $filename)
    {
        $existingAsset = $this->getAssetById($assetId);

        if (!$existingAsset) {
            throw new AssetLogicException(Craft::t('app',
                'The asset to be replaced cannot be found.'));
        }

        if (Io::getFileKind(Io::getExtension($pathOnServer)) == 'image') {
            Image::cleanImageByPath($pathOnServer);
        }

        $event = new ReplaceAssetEvent([
            'asset' => $existingAsset,
            'replaceWith' => $pathOnServer,
            'filename' => $filename
        ]);

        $this->trigger(static::EVENT_BEFORE_REPLACE_ASSET, $event);

        // Is the event preventing this from happening?
        if (!$event->isValid) {
            throw new ActionCancelledException(Craft::t('app',
                'Something prevented the Asset file from being replaced.'));
        }

        // TODO check event

        $existingAsset = $this->getAssetById($assetId);

        $volume = $existingAsset->getVolume();

        // Clear all thumb and transform data
        if (Image::isImageManipulatable($existingAsset->getExtension())) {
            Craft::$app->getAssetTransforms()->deleteAllTransformData($existingAsset);
        }

        // Open the stream for, uhh, streaming
        $stream = fopen($pathOnServer, 'r');

        if (!$stream) {
            throw new FileException(Craft::t('app',
                'Could not open file for streaming at {path}',
                ['path' => $pathOnServer]));
        }

        // Re-use the same filename
        if (StringHelper::toLowerCase($existingAsset->filename) == StringHelper::toLowerCase($filename)) {
            // The case is changing in the filename
            if ($existingAsset->filename != $filename) {
                // Delete old, change the name, upload the new
                $volume->deleteFile($existingAsset->getUri());
                $existingAsset->filename = $filename;
                $volume->createFileByStream($existingAsset->getUri(), $stream);
            } else {
                $volume->updateFileByStream($existingAsset->getUri(), $stream);
            }
        } else {
            // Get an available name to avoid conflicts and upload the file
            $filename = $this->getNameReplacementInFolder($filename,
                $existingAsset->getFolder());

            // Delete old, change the name, upload the new
            $volume->deleteFile($existingAsset->getUri());
            $existingAsset->filename = $filename;
            $volume->createFileByStream($existingAsset->getUri(), $stream);

            $existingAsset->kind = Io::getFileKind(Io::getExtension($filename));
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($existingAsset->kind == "image") {
            list ($existingAsset->width, $existingAsset->height) = Image::getImageSize($pathOnServer);
        } else {
            $existingAsset->width = null;
            $existingAsset->height = null;
        }

        $existingAsset->size = Io::getFileSize($pathOnServer);
        $existingAsset->dateModified = Io::getLastTimeModified($pathOnServer);

        $this->saveAsset($existingAsset);

        $event = new ReplaceAssetEvent([
            'asset' => $existingAsset,
            'filename' => $filename
        ]);
        $this->trigger(static::EVENT_AFTER_REPLACE_ASSET, $event);
    }

    /**
     * Delete a list of files by an array of ids (or a single id).
     *
     * @param array|int $assetIds
     * @param boolean $deleteFile Should the file be deleted along the record. Defaults to true.
     *
     * @return void
     */
    public function deleteAssetsByIds($assetIds, $deleteFile = true)
    {
        if (!is_array($assetIds)) {
            $assetIds = [$assetIds];
        }

        foreach ($assetIds as $assetId) {
            $asset = $this->getAssetById($assetId);

            if ($asset) {
                $volume = $asset->getVolume();

                // Fire an 'onBeforeDeleteAsset' event
                $event = new AssetEvent($this, [
                    'asset' => $asset
                ]);
                $this->trigger(static::EVENT_BEFORE_DELETE_ASSET, $event);

                if ($event->isValid) {
                    if ($deleteFile) {
                        $volume->deleteFile($asset->getUri());
                    }

                    Craft::$app->getElements()->deleteElementById($assetId);
                    Craft::$app->getAssetTransforms()->deleteAllTransformData($asset);

                    $this->trigger(static::EVENT_AFTER_DELETE_ASSET, $event);
                }
            }
        }
    }

    /**
     * Rename an Asset.
     *
     * @param Asset $asset
     * @param string $newFilename
     *
     * @throws AssetDisallowedExtensionException If the extension is not allowed.
     * @throws AssetConflictException            If a file with such a name already exists.
     * @throws AssetLogicException               If the Volume is missing.
     * @return void
     */
    public function renameAsset(Asset $asset, $newFilename)
    {
        $extension = Io::getExtension($newFilename);

        if (!Io::isExtensionAllowed($extension)) {
            throw new AssetDisallowedExtensionException(Craft::t('app',
                'The extension “{extension}” is not allowed.',
                ['extension' => $extension]));
        }

        $newFilename = AssetsHelper::prepareAssetName($newFilename);

        $existingAsset = $this->findAsset([
            'filename' => $newFilename,
            'folderId' => $asset->folderId
        ]);

        if ($existingAsset && $existingAsset->id != $asset->id) {
            throw new AssetConflictException(Craft::t('app',
                'A file with the name “{filename}” already exists in the folder.',
                ['filename' => $newFilename]));
        }

        $volume = $asset->getVolume();

        if (!$volume) {
            throw new AssetLogicException(Craft::t('app',
                'Volume does not exist with the id of {id}.',
                ['id' => $asset->volumeId]));
        }

        if ($volume->renameFile($asset->getUri(), $asset->getUri($newFilename))
        ) {
            $asset->filename = $newFilename;
            $this->_storeAssetRecord($asset);
        }
    }

    /**
     * Save an Asset folder.
     *
     * @param VolumeFolderModel $folder
     *
     * @throws AssetConflictException           If a folder already exists with such a name.
     * @throws AssetLogicException              If the parent folder is missing.
     * @throws VolumeObjectExistsException      If the file actually exists on the volume, but on in the index.
     * @return void
     */
    public function createFolder(VolumeFolderModel $folder)
    {
        $parent = $folder->getParent();

        if (!$parent) {
            throw new AssetLogicException(Craft::t('app',
                'No folder exists with the ID “{id}”',
                ['id' => $folder->parentId]));
        }

        $existingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $folder->name
        ]);

        if ($existingFolder && (empty($folder->id) || $folder->id != $existingFolder)) {
            throw new AssetConflictException(Craft::t('app',
                'A folder with the name “{folderName}” already exists in the folder.',
                ['folderName' => $folder->name]));
        }

        $volume = $parent->getVolume();

        // Explicitly re-throw VolumeObjectExistsException
        try {
            $volume->createDir(rtrim($folder->path, '/'));
        } catch (VolumeObjectExistsException $exception) {
            // Rethrow exception unless this is a temporary Volume.
            if (!is_null($folder->volumeId)) {
                throw $exception;
            }
        }
        $this->storeFolderRecord($folder);
    }

    /**
     * Rename a folder by it's id.
     *
     * @param $folderId
     * @param $newName
     *
     * @throws AssetConflictException           If a folder already exists with such name in Assets Index
     * @throws AssetLogicException              If the folder to be renamed can't be found or trying to rename the top folder.
     * @throws VolumeObjectExistsException      If a folder already exists with such name in the Volume, but not in Index
     * @throws VolumeObjectNotFoundException    If the folder to be renamed can't be found in the Volume.
     * @return string $newName The new folder name after cleaning it.
     */
    public function renameFolderById($folderId, $newName)
    {
        $newName = AssetsHelper::prepareAssetName($newName, false);
        $folder = $this->getFolderById($folderId);

        if (!$folder) {
            throw new AssetLogicException(Craft::t('app',
                'No folder exists with the ID “{id}”',
                ['id' => $folderId]));
        }

        if (!$folder->parentId) {
            throw new AssetLogicException(Craft::t('app',
                "It's not possible to rename the top folder of a Volume."));
        }

        $conflictingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $newName
        ]);

        if ($conflictingFolder) {
            throw new AssetConflictException(Craft::t('app',
                'A folder with the name “{folderName}” already exists in the folder.',
                ['folderName' => $folder->name]));
        }

        $volume = $folder->getVolume();

        $volume->renameDir(rtrim($folder->path, '/'), $newName);

        $descendantFolders = $this->getAllDescendantFolders($folder);
        $newFullPath = IO::getParentFolderPath($folder->path).$newName.'/';

        foreach ($descendantFolders as $descendantFolder) {
            $descendantFolder->path = preg_replace('#^'.$folder->path.'#',
                $newFullPath, $descendantFolder->path);
            $this->storeFolderRecord($descendantFolder);
        }

        // Now change the affected folder
        $folder->name = $newName;
        $folder->path = $newFullPath;
        $this->storeFolderRecord($folder);

        return $newName;
    }

    /**
     * Deletes a folder by its ID.
     *
     * @param array|int $folderIds
     * @param boolean $deleteFolder Should the folder be deleted along the record. Defaults to true.
     *
     * @throws VolumeException If deleting a single folder and it cannot be deleted.
     * @return void
     */
    public function deleteFoldersByIds($folderIds, $deleteFolder = true)
    {
        if (!is_array($folderIds)) {
            $folderIds = [$folderIds];
        }

        foreach ($folderIds as $folderId) {
            $folder = $this->getFolderById($folderId);

            if ($folder) {
                if ($deleteFolder) {
                    $volume = $folder->getVolume();
                    $volume->deleteDir($folder->path);
                }

                VolumeFolderRecord::deleteAll(['id' => $folderId]);
            }
        }
    }

    /**
     * Get the folder tree for Assets by volume ids
     *
     * @param $allowedVolumeIds
     *
     * @return array
     */
    public function getFolderTreeByVolumeIds($allowedVolumeIds)
    {
        $folders = $this->findFolders([
            'volumeId' => $allowedVolumeIds,
            'order' => 'path'
        ]);
        $tree = $this->_getFolderTreeByFolders($folders);

        $sort = [];

        foreach ($tree as $topFolder) {
            /**
             * @var VolumeFolderModel $topFolder ;
             */
            $sort[] = $topFolder->getVolume()->sortOrder;
        }

        array_multisort($sort, $tree);

        return $tree;
    }

    /**
     * Get the folder tree for Assets by a folder id.
     *
     * @param $folderId
     *
     * @return array
     */
    public function getFolderTreeByFolderId($folderId)
    {
        $folder = $this->getFolderById($folderId);

        if (is_null($folder)) {
            return [];
        }

        return $this->_getFolderTreeByFolders([$folder]);
    }

    /**
     * Returns a folder by its ID.
     *
     * @param integer $folderId
     *
     * @return VolumeFolderModel|null
     */
    public function getFolderById($folderId)
    {
        if (!isset($this->_foldersById) || !array_key_exists($folderId,
                $this->_foldersById)
        ) {
            $result = $this->_createFolderQuery()
                ->where('id = :id', [':id' => $folderId])
                ->one();

            if ($result) {
                $folder = new VolumeFolderModel($result);
            } else {
                $folder = null;
            }

            $this->_foldersById[$folderId] = $folder;
        }

        return $this->_foldersById[$folderId];
    }

    /**
     * Finds folders that match a given criteria.
     *
     * @param mixed $criteria
     *
     * @return array
     */
    public function findFolders($criteria = null)
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->select('f.*')
            ->from('{{%volumefolders}} AS f');

        $this->_applyFolderConditions($query, $criteria);

        if ($criteria->order) {
            $query->orderBy($criteria->order);
        }

        if ($criteria->offset) {
            $query->offset($criteria->offset);
        }

        if ($criteria->limit) {
            $query->limit($criteria->limit);
        }

        $results = $query->all();
        $folders = [];

        foreach ($results as $result) {
            $folder = VolumeFolderModel::create($result);
            $this->_foldersById[$folder->id] = $folder;
            $folders[] = $folder;
        }

        return $folders;
    }

    /**
     * Returns all of the folders that are descendants of a given folder.
     *
     * @param VolumeFolderModel $parentFolder
     * @param string $orderBy
     *
     * @return array
     */
    public function getAllDescendantFolders(
        VolumeFolderModel $parentFolder,
        $orderBy = "path"
    ) {
        /**
         * @var $query Query
         */
        $query = (new Query())
            ->select('f.*')
            ->from('{{%volumefolders}} AS f')
            ->where(['like', 'path', $parentFolder->path.'%', false])
            ->andWhere('volumeId = :volumeId',
                [':volumeId' => $parentFolder->volumeId])
            ->andWhere('parentId IS NOT NULL');

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        $results = $query->all();
        $descendantFolders = [];

        foreach ($results as $result) {
            $folder = VolumeFolderModel::create($result);
            $this->_foldersById[$folder->id] = $folder;
            $descendantFolders[$folder->id] = $folder;
        }

        return $descendantFolders;
    }

    /**
     * Finds the first folder that matches a given criteria.
     *
     * @param mixed $criteria
     *
     * @return VolumeFolderModel|null
     */
    public function findFolder($criteria = null)
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $criteria->limit = 1;
        $folder = $this->findFolders($criteria);

        if (is_array($folder) && !empty($folder)) {
            return array_pop($folder);
        }

        return null;
    }

    /**
     * Gets the total number of folders that match a given criteria.
     *
     * @param mixed $criteria
     *
     * @return integer
     */
    public function getTotalFolders($criteria)
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->select('count(id)')
            ->from('{{%volumefolders}} AS f');

        $this->_applyFolderConditions($query, $criteria);

        return (int)$query->scalar();
    }

    // File and folder managing
    // -------------------------------------------------------------------------


    /**
     * Get URL for a file.
     *
     * @param Asset $asset
     * @param string $transform
     *
     * @return string
     */
    public function getUrlForAsset(Asset $asset, $transform = null)
    {
        //TODO Asset thumb cache bust?
        if (!$transform || !Image::isImageManipulatable(Io::getExtension($asset->filename))) {
            $volume = $asset->getVolume();

            return AssetsHelper::generateUrl($volume, $asset);
        }

        // Get the transform index model
        $assetTransforms = Craft::$app->getAssetTransforms();
        $index = $assetTransforms->getTransformIndex($asset,
            $transform);

        // Does the file actually exist?
        if ($index->fileExists) {
            return $assetTransforms->getUrlForTransformByTransformIndex($index);
        } else {
            if (Craft::$app->getConfig()->get('generateTransformsBeforePageLoad')) {
                return $assetTransforms->ensureTransformUrlByIndexModel($index);
            } else {
                // Queue up a new Generate Pending Transforms task, if there isn't one already
                $tasks = Craft::$app->getTasks();
                if (!$tasks->areTasksPending('GeneratePendingTransforms')) {
                    $tasks->createTask('GeneratePendingTransforms');
                }

                // Return the temporary transform URL
                return Url::getResourceUrl('transforms/'.$index->id);
            }
        }
    }

    /**
     * Find a replacement for a filename
     *
     * @param string $originalFilename the original filename for which to find a replacement.
     * @param VolumeFolderModel $folder THe folder in which to find the replacement
     *
     * @throws AssetLogicException If a suitable filename replacement cannot be found.
     * @return string
     */
    public function getNameReplacementInFolder(
        $originalFilename,
        VolumeFolderModel $folder
    ) {
        $volume = $folder->getVolume();
        $fileList = $volume->getFileList($folder->path);

        // Flip the array for faster lookup
        $existingFiles = [];

        foreach ($fileList as $file) {
            if (StringHelper::toLowerCase(rtrim($folder->path,
                    '/')) == StringHelper::toLowerCase($file['dirname'])
            ) {
                $existingFiles[StringHelper::toLowerCase($file['basename'])] = true;
            }
        }

        // Shorthand.
        $canUse = function ($filenameToTest) use ($existingFiles) {
            return !isset($existingFiles[StringHelper::toLowerCase($filenameToTest)]);
        };

        if ($canUse($originalFilename)) {
            return $originalFilename;
        }

        $extension = IO::getExtension($originalFilename);
        $filename = IO::getFileName($originalFilename, false);


        // If the file already ends with something that looks like a timestamp, use that instead.
        if (preg_match('/.*_([0-9]{6}_[0-9]{6})$/', $filename, $matches)) {
            $base = $filename;
        } else {
            $timestamp = DateTimeHelper::currentUTCDateTime()->format("ymd_His");
            $base = $filename.'_'.$timestamp;
        }

        $newFilename = $base.'.'.$extension;

        if ($canUse($newFilename)) {
            return $newFilename;
        }

        $increment = 0;

        while (++$increment) {
            $newFilename = $base.'_'.$increment.'.'.$extension;

            if ($canUse($newFilename)) {
                break;
            }

            if ($increment == 50) {
                throw new AssetLogicException(Craft::t('app',
                    'Could not find a suitable replacement filename for “{filename}”.',
                    ['filename' => $filename]));

            }
        }

        return $newFilename;
    }

    /**
     * Move an Asset.
     *
     * @param Asset $asset
     * @param integer $folderId Id of the folder of the destination
     * @param string $newFilename filename to use for the file at it's destination
     *
     * @throws AssetDisallowedExtensionException If the extension is not allowed.
     * @throws AssetConflictException            If there is a conflict.
     * @throws AssetLogicException               If the target folder does not exist.
     * @return void
     */
    public function moveAsset(Asset $asset, $folderId, $newFilename = "")
    {
        $filename = $newFilename ?: $asset->filename;

        $extension = Io::getExtension($filename);

        if (!Io::isExtensionAllowed($extension)) {
            throw new AssetDisallowedExtensionException(Craft::t('app',
                'The extension “{extension}” is not allowed.',
                ['extension' => $extension]));
        }

        $existingAsset = $this->findAsset([
            'filename' => $filename,
            'folderId' => $folderId
        ]);

        if ($existingAsset && $existingAsset->id != $asset->id) {
            throw new AssetConflictException(Craft::t('app',
                'A file with the name “{filename}” already exists in the folder.',
                ['filename' => $filename]));
        }

        $targetFolder = $this->getFolderById($folderId);

        if (!$targetFolder) {
            throw new AssetLogicException(Craft::t('app',
                'The destination folder does not exist'));
        }

        $this->_moveAssetToFolder($asset, $targetFolder, $filename);

        $asset->folderId = $folderId;
        $asset->volumeId = $targetFolder->volumeId;
        $asset->filename = $filename;

        $this->saveAsset($asset);
    }


    /**
     * Return true if user has permission to perform the action on the folder.
     *
     * @param $folderId
     * @param $action
     *
     * @return boolean
     */
    public function canUserPerformAction($folderId, $action)
    {
        try {
            $this->checkPermissionByFolderIds($folderId, $action);

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Check for a permission on a volumeId by a folder id or an array of folder ids.
     *
     * @param $folderIds
     * @param $permission
     *
     * @throws Exception
     * @return void
     */
    public function checkPermissionByFolderIds($folderIds, $permission)
    {
        // TODO permissions.
        if (!is_array($folderIds)) {
            $folderIds = [$folderIds];
        }

        foreach ($folderIds as $folderId) {
            $folderModel = $this->getFolderById($folderId);

            if (!$folderModel) {
                throw new Exception(Craft::t('app',
                    'That folder does not seem to exist anymore. Re-index the Assets volume and try again.'));
            }

            if (!Craft::$app->user->checkPermission($permission.':'.$folderModel->volumeId)) {
                throw new Exception(Craft::t('app',
                    'You don’t have the required permissions for this operation.'));
            }
        }
    }

    /**
     * Check for a permission on a volume by a file id or an array of file ids.
     *
     * @param $fileIds
     * @param $permission
     *
     * @throws Exception
     * @return void
     */
    public function checkPermissionByFileIds($fileIds, $permission)
    {
        // TODO permissions.
        if (!is_array($fileIds)) {
            $fileIds = [$fileIds];
        }

        foreach ($fileIds as $fileId) {
            $file = $this->getAssetById($fileId);

            if (!$file) {
                throw new Exception(Craft::t('app',
                    'That file does not seem to exist anymore. Re-index the Assets volume and try again.'));
            }

            if (!Craft::$app->user->checkPermission($permission.':'.$file->volumeId)) {
                throw new Exception(Craft::t('app',
                    'You don’t have the required permissions for this operation.'));
            }
        }
    }

    /**
     * Ensure a folder entry exists in the DB for the full path and return it's id.
     *
     * @param string $fullPath The path to ensure the folder exists at.
     *
     * @return integer
     */
    public function ensureFolderByFullPathAndVolumeId($fullPath, $volumeId)
    {
        $parameters = new FolderCriteria([
            'path' => $fullPath,
            'volumeId' => $volumeId
        ]);

        $folderModel = $this->findFolder($parameters);

        // If we don't have a folder matching these, create a new one
        if (is_null($folderModel)) {
            $parts = explode('/', rtrim($fullPath, '/'));
            $folderName = array_pop($parts);

            if (empty($parts)) {
                // Looking for a top level folder, apparently.
                $parameters->path = '';
                $parameters->parentId = ':empty:';
            } else {
                $parameters->path = join('/', $parts).'/';
            }

            // Look up the parent folder
            $parentFolder = $this->findFolder($parameters);

            if (is_null($parentFolder)) {
                $parentId = ':empty:';
            } else {
                $parentId = $parentFolder->id;
            }

            $folderModel = new VolumeFolderModel();
            $folderModel->volumeId = $volumeId;
            $folderModel->parentId = $parentId;
            $folderModel->name = $folderName;
            $folderModel->path = $fullPath;

            $this->storeFolderRecord($folderModel);
        }

        return $folderModel->id;
    }

    /**
     * Store a folder by model
     *
     * @param VolumeFolderModel $folder
     *
     * @return boolean
     */
    public function storeFolderRecord(VolumeFolderModel $folder)
    {
        if (empty($folder->id)) {
            $record = new VolumeFolderRecord();
        } else {
            $record = VolumeFolderRecord::findOne(['id' => $folder->id]);
        }

        $record->parentId = $folder->parentId;
        $record->volumeId = $folder->volumeId;
        $record->name = $folder->name;
        $record->path = $folder->path;
        $record->save();

        $folder->id = $record->id;
    }

    /**
     * Get the user's folder.
     *
     * @param User $userModel
     *
     * @return VolumeFolderModel|null
     */
    public function getUserFolder(User $userModel = null)
    {
        $sourceTopFolder = $this->findFolder([
            'volumeId' => ':empty:',
            'parentId' => ':empty:'
        ]);

        // Unlikely, but would be very awkward if this happened without any contingency plans in place.
        if (!$sourceTopFolder) {
            $sourceTopFolder = new VolumeFolderModel();
            $tempSource = new Temp();
            $sourceTopFolder->name = $tempSource->name;
            $this->storeFolderRecord($sourceTopFolder);
        }

        if ($userModel) {
            $folderName = 'user_'.$userModel->id;
        } else {
            // A little obfuscation never hurt anyone
            $folderName = 'user_'.sha1(Craft::$app->getSession()->id);
        }


        $folder = $this->findFolder([
            'name' => $folderName,
            'parentId' => $sourceTopFolder->id
        ]);

        if (!$folder) {
            $folder = new VolumeFolderModel();
            $folder->parentId = $sourceTopFolder->id;
            $folder->name = $folderName;
            $folder->path = $folderName.'/';
            $this->storeFolderRecord($folder);
        }

        return $folder;
    }

    // Private Methods
    // =========================================================================

    /**
     * Move an Asset's file to the specified folder.
     *
     * @param Asset $asset
     * @param VolumeFolderModel $targetFolder
     * @param string $newFilename new filename to use
     *
     * @throws FileException If there was a problem with the actual file.
     * @return void
     */
    private function _moveAssetToFolder(
        Asset $asset,
        VolumeFolderModel $targetFolder,
        $newFilename = ""
    ) {
        $filename = $newFilename ?: $asset->filename;

        $sourceVolume = $asset->getVolume();
        $fromPath = $asset->getUri();
        $toPath = $targetFolder->path.$filename;

        // Move inside the source.
        $assetTransforms = Craft::$app->getAssetTransforms();
        if ($asset->volumeId == $targetFolder->volumeId) {
            if ($fromPath == $toPath) {
                return;
            }

            $sourceVolume->renameFile($fromPath, $toPath);
            $transformIndexes = $assetTransforms->getAllCreatedTransformsForAsset($asset);

            // Move the transforms
            foreach ($transformIndexes as $transformIndex) {
                /**
                 * @var AssetTransformIndex $transformIndex
                 */
                $fromTransformPath = $assetTransforms->getTransformSubpath($asset,
                    $transformIndex);
                $toTransformPath = $fromTransformPath;

                // In case we're changing the filename, make sure that we're not missing that.
                $parts = explode("/", $toTransformPath);
                $transformName = array_pop($parts);
                $toTransformPath = join("/",
                        $parts).'/'.Io::getFilename($filename,
                        false).'.'.Io::getExtension($transformName);

                $baseFrom = $asset->getFolder()->path;
                $baseTo = $targetFolder->path;

                // Overwrite existing transforms
                $sourceVolume->deleteFile($baseTo.$toTransformPath);

                try {
                    $sourceVolume->renameFile($baseFrom.$fromTransformPath,
                        $baseTo.$toTransformPath);
                    $transformIndex->filename = $filename;
                    $assetTransforms->storeTransformIndexData($transformIndex);
                } catch (VolumeObjectNotFoundException $exception) {
                    // No biggie, just delete the transform index as well then
                    $assetTransforms->deleteTransformIndex($transformIndex->id);
                }
            }
        } // Move between sources
        else {
            $localPath = Io::getTempFilePath($asset->getExtension());
            $sourceVolume->saveFileLocally($asset->getUri(), $localPath);
            $targetVolume = $targetFolder->getVolume();
            $stream = fopen($localPath, 'r');

            if (!$stream) {
                throw new FileException(Craft::t('app',
                    'Could not open file for streaming at {path}',
                    ['path' => $asset->newFilePath]));
            }

            $targetVolume->createFileByStream($toPath, $stream);
            $sourceVolume->deleteFile($asset->getUri());

            if (is_resource($stream)) {
                fclose($stream);
            }

            // Nuke the transforms
            $assetTransforms->deleteAllTransformData($asset);
        }
    }

    /**
     * Returns an AssetQuery object prepped for retrieving assets.
     *
     * @return AssetQuery
     */
    private function _createAssetQuery($criteria)
    {
        if ($criteria instanceof AssetQuery) {
            $query = $criteria;
        } else {
            $query = Asset::find()->configure($criteria);
        }

        if (is_string($query->filename)) {
            // Backslash-escape any commas in a given string.
            $query->filename = Db::escapeParam($query->filename);
        }

        return $query;
    }

    /**
     * Returns a DbCommand object prepped for retrieving assets.
     *
     * @return Query
     */
    private function _createFolderQuery()
    {
        return (new Query())
            ->select('id, parentId, volumeId, name, path')
            ->from('{{%volumefolders}}');
    }

    /**
     * Return the folder tree form a list of folders.
     *
     * @param $folders
     *
     * @return array
     */
    private function _getFolderTreeByFolders($folders)
    {
        $tree = [];
        $referenceStore = [];

        foreach ($folders as $folder) {
            /**
             * @var VolumeFolder $folder
             */
            if ($folder->parentId && isset($referenceStore[$folder->parentId])) {
                $referenceStore[$folder->parentId]->addChild($folder);
            } else {
                $tree[] = $folder;
            }

            $referenceStore[$folder->id] = $folder;
        }

        $sort = [];

        foreach ($tree as $topFolder) {
            /**
             * @var VolumeFolder $topFolder
             */
            $sort[] = $topFolder->getVolume()->sortOrder;
        }

        array_multisort($sort, $tree);

        return $tree;
    }

    /**
     * Applies WHERE conditions to a DbCommand query for folders.
     *
     * @param Query $query
     * @param FolderCriteria $criteria
     *
     * @return void
     */
    private function _applyFolderConditions($query, FolderCriteria $criteria)
    {
        $whereConditions = [];
        $whereParams = [];

        if ($criteria->id) {
            $whereConditions[] = Db::parseParam('f.id', $criteria->id,
                $whereParams);
        }

        if ($criteria->volumeId) {
            $whereConditions[] = Db::parseParam('f.volumeId',
                $criteria->volumeId, $whereParams);
        }

        if ($criteria->parentId) {
            $whereConditions[] = Db::parseParam('f.parentId',
                $criteria->parentId, $whereParams);
        }

        if ($criteria->name) {
            $whereConditions[] = Db::parseParam('f.name', $criteria->name,
                $whereParams);
        }

        if (!is_null($criteria->path)) {
            // This folder has a comma in it.
            if (strpos($criteria->path, ',') !== false) {
                // Escape the comma.
                $condition = Db::parseParam('f.path',
                    str_replace(',', '\,', $criteria->path), $whereParams);
                $lastKey = key(array_slice($whereParams, -1, 1, true));

                // Now un-escape it.
                $whereParams[$lastKey] = str_replace('\,', ',',
                    $whereParams[$lastKey]);
            } else {
                $condition = Db::parseParam('f.path', $criteria->path,
                    $whereParams);
            }

            $whereConditions[] = $condition;
        }

        if (count($whereConditions) == 1) {
            $query->where($whereConditions[0], $whereParams);
        } else {
            array_unshift($whereConditions, 'and');
            $query->where($whereConditions, $whereParams);
        }
    }

    /**
     * Saves the record for an asset.
     *
     * @param Asset $asset
     *
     * @throws AssetMissingException    If attempting to update a non-existing Asset.
     * @throws ValidationException      If the validation failed.
     * @throws ElementSaveException     If the element failed to save.
     * @throws ActionCancelledException If something prevented the Asset replacement via Event
     * @throws \Exception               If something else went wrong.
     * @return boolean
     */
    private function _storeAssetRecord(Asset $asset)
    {
        $isNewAsset = !$asset->id;

        if (!$isNewAsset) {
            $assetRecord = AssetRecord::findOne(['id' => $asset->id]);

            if (!$assetRecord) {
                throw new AssetMissingException(Craft::t('app',
                    'No asset exists with the ID “{id}”.',
                    ['id' => $asset->id]));
            }
        } else {
            $assetRecord = new AssetRecord();
        }

        $assetRecord->volumeId = $asset->volumeId;
        $assetRecord->folderId = $asset->folderId;
        $assetRecord->filename = $asset->filename;
        $assetRecord->kind = $asset->kind;
        $assetRecord->size = $asset->size;
        $assetRecord->width = $asset->width;
        $assetRecord->height = $asset->height;
        $assetRecord->dateModified = $asset->dateModified;

        $assetRecord->validate();
        $asset->addErrors($assetRecord->getErrors());

        if ($asset->hasErrors()) {
            $exception = new ValidationException(
                Craft::t('app',
                    'Saving the Asset failed with the following errors: {errors}',
                    ['errors' => join(', ', $asset->getAllErrors())])
            );

            $exception->setModel($asset);

            throw $exception;
        }

        if ($isNewAsset && !$asset->title) {
            // Give it a default title based on the file name
            $asset->title = $asset->generateAttributeLabel(Io::getFilename($asset->filename,
                false));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $event = new AssetEvent([
                'asset' => $asset
            ]);
            $this->trigger(static::EVENT_BEFORE_SAVE_ASSET, $event);

            // Is the event giving us the go-ahead?
            if ($event->isValid) {
                // Save the element
                $success = Craft::$app->getElements()->saveElement($asset,
                    false);

                // If it didn't work, rollback the transaction in case something changed in onBeforeSaveAsset
                if (!$success) {
                    $transaction->rollback();

                    throw new ElementSaveException(Craft::t('app',
                        'Failed to save the Asset Element'));
                }

                // Now that we have an element ID, save it on the other stuff
                if ($isNewAsset) {
                    $assetRecord->id = $asset->id;
                }

                // Save the record
                $assetRecord->save(false);

                $this->trigger(static::EVENT_AFTER_SAVE_ASSET, $event);
            } else {
                throw new ActionCancelledException(Craft::t('app',
                    'A plugin cancelled the save operation for {asset}!',
                    ['asset' => $asset->filename]));
            }

            // Commit the transaction regardless of whether we saved the asset, in case something changed
            // in onBeforeSaveAsset
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
}
