<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\User;
use craft\errors\ActionCancelledException;
use craft\errors\AssetConflictException;
use craft\errors\AssetDisallowedExtensionException;
use craft\errors\AssetLogicException;
use craft\errors\FileException;
use craft\errors\ImageException;
use craft\errors\UploadFailedException;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\events\AssetEvent;
use craft\events\ReplaceAssetEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\AssetTransform;
use craft\models\AssetTransformIndex;
use craft\models\FolderCriteria;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\records\VolumeFolder as VolumeFolderRecord;
use craft\tasks\GeneratePendingTransforms;
use craft\volumes\Temp;
use DateTime;
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
     * @event AssetEvent The event that is triggered before an asset is replaced.
     *
     * You may set [[AssetEvent::isValid]] to `false` to prevent the asset from being replaced.
     */
    const EVENT_BEFORE_REPLACE_ASSET = 'beforeReplaceFile';

    /**
     * @event AssetEvent The event that is triggered after an asset is replaced.
     */
    const EVENT_AFTER_REPLACE_ASSET = 'afterReplaceFile';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_foldersById;

    // Public Methods
    // =========================================================================

    /**
     * Returns a file by its ID.
     *
     * @param int      $assetId
     * @param int|null $siteId
     *
     * @return Asset|null
     */
    public function getAssetById(int $assetId, int $siteId = null)
    {
        /** @var Asset|null $asset */
        $asset = Craft::$app->getElements()->getElementById($assetId, Asset::class, $siteId);

        return $asset;
    }

    /**
     * Gets the total number of assets that match a given criteria.
     *
     * @param mixed $criteria
     *
     * @return int
     */
    public function getTotalAssets($criteria = null): int
    {
        if ($criteria instanceof AssetQuery) {
            $query = $criteria;
        } else {
            $query = Asset::find();
            if ($criteria) {
                Craft::configure($query, $criteria);
            }
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

        if ($isNew) {
            $asset->folderPath = $asset->getFolder()->path;
        }

        if ($isNew && empty($asset->newFilePath) && empty($asset->indexInProgress)) {
            throw new AssetLogicException(Craft::t('app',
                'A new Asset cannot be created without a file.'));
        }

        if (empty($asset->folderId)) {
            throw new AssetLogicException(Craft::t('app',
                'All Assets must have folder ID set.'));
        }

        $extension = $asset->getExtension();

        if (!Craft::$app->getConfig()->isExtensionAllowed($extension)) {
            throw new AssetDisallowedExtensionException(Craft::t('app',
                'The extension “{extension}” is not allowed.',
                ['extension' => $extension]));
        }

        $existingAsset = Asset::find()
            ->folderId($asset->folderId)
            ->filename(Db::escapeParam($asset->filename))
            ->one();

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
            if (AssetsHelper::getFileKindByExtension($asset->newFilePath) === 'image') {
                Image::cleanImageByPath($asset->newFilePath);
            }

            $stream = fopen($asset->newFilePath, 'rb');

            if (!$stream) {
                throw new FileException(Craft::t('app',
                    'Could not open file for streaming at {path}',
                    ['path' => $asset->newFilePath]));
            }

            $uriPath = $asset->getUri();

            $event = new AssetEvent(['asset' => $asset, 'isNew' => $isNew]);
            $this->trigger(self::EVENT_BEFORE_UPLOAD_ASSET, $event);

            // Explicitly re-throw VolumeFileExistsException
            try {
                $result = $volume->createFileByStream($uriPath, $stream, []);
            } catch (VolumeObjectExistsException $exception) {
                // Replace the file if this is the temporary Volume.
                if ($asset->volumeId === null) {
                    $volume->deleteFile($uriPath);
                    $result = $volume->createFileByStream($uriPath, $stream, []);
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
            $asset->size = filesize($asset->newFilePath);
            $asset->kind = AssetsHelper::getFileKindByExtension($asset->filename);

            if ($asset->kind === 'image' && !empty($asset->newFilePath)) {

                list ($asset->width, $asset->height) = Image::imageSize($asset->newFilePath);
            }
        }

        Craft::$app->getElements()->saveElement($asset);

        // Now that we have an ID, store the source
        if (!$volume instanceof LocalVolumeInterface && $asset->kind === 'image' && !empty($asset->newFilePath)) {
            // Store the local source for now and set it up for deleting, if needed
            $assetTransforms = Craft::$app->getAssetTransforms();
            $assetTransforms->storeLocalSource($asset->newFilePath, $asset->getImageTransformSourcePath());
            $assetTransforms->queueSourceForDeletingIfNecessary($asset->getImageTransformSourcePath());
        }
    }

    /**
     * Replaces an Asset with another.
     *
     * @param Asset $assetToReplace
     * @param Asset $assetToReplaceWith
     * @param bool  $mergeRelationships should the relations be merged for the conflicting Assets
     *
     * @return void
     */
    public function replaceAsset(Asset $assetToReplace, Asset $assetToReplaceWith, bool $mergeRelationships = false)
    {
        $targetVolume = $assetToReplace->getVolume();

        // TODO purge cached files for remote Volumes.

        // Clear all thumb and transform data
        if (Image::isImageManipulatable($assetToReplace->getExtension())) {
            Craft::$app->getAssetTransforms()->deleteAllTransformData($assetToReplace);
        }

        // Handle things differently depending if the relationships should be merged
        // for the Asset being replaced.
        if ($mergeRelationships) {
            // This deletes the $assetToReplace element and merges their relationships.
            Craft::$app->getElements()->mergeElementsByIds($assetToReplace->id,
                $assetToReplaceWith->id);

            // Replace the file - delete the conflicting file and move the new file in it's place.
            $targetVolume->deleteFile($assetToReplace->getUri());
            $this->_moveAssetFileToFolder($assetToReplaceWith,
                $assetToReplace->getFolder());

            // Update attribute on the Asset being merged into an existing one
            // because the target Asset is nuked and the new one takes it's place.
            $assetToReplaceWith->folderId = $assetToReplace->folderId;
            $assetToReplaceWith->volumeId = $assetToReplace->volumeId;
            $assetToReplaceWith->filename = $assetToReplace->filename;

            // At this point the Asset being moved effectively takes place of the target Asset.
            $this->saveAsset($assetToReplaceWith);
        } else {
            // Update the file-related attributes on the target Asset based on the incoming Asset
            $assetToReplace->dateModified = $assetToReplaceWith->dateModified;
            $assetToReplace->size = $assetToReplaceWith->size;
            $assetToReplace->kind = $assetToReplaceWith->kind;
            $assetToReplace->width = $assetToReplaceWith->width;
            $assetToReplace->height = $assetToReplaceWith->height;

            // Replace the file - delete the conflicting file and move the new file in it's place.
            $targetVolume->deleteFile($assetToReplace->getUri());
            $this->_moveAssetFileToFolder($assetToReplaceWith,
                $assetToReplace->getFolder(), $assetToReplace->filename);

            // At this point the existing Asset has its properties changed and the Asset
            // file itself is changed as well. Save the existing Asset and delete the other one.
            $this->saveAsset($assetToReplace);
            $assetToReplaceWith->keepFileOnDelete = true;
            Craft::$app->getElements()->saveElement($assetToReplaceWith);
        }
    }

    /**
     * Replace an Asset's file.
     *
     * Replace an Asset's file by it's id, a local file and the filename to use.
     *
     * @param Asset  $asset
     * @param string $pathOnServer
     * @param string $filename
     *
     *
     * @throws ActionCancelledException If something prevented the Asset replacement via Event.
     * @throws FileException            If there was a problem with the actual file.
     * @throws AssetLogicException      If the Asset to be replaced cannot be found.
     * @return void
     */
    public function replaceAssetFile(Asset $asset, string $pathOnServer, string $filename)
    {
        if (AssetsHelper::getFileKindByExtension($pathOnServer) === 'image') {
            Image::cleanImageByPath($pathOnServer);
        }

        $event = new ReplaceAssetEvent([
            'asset' => $asset,
            'replaceWith' => $pathOnServer,
            'filename' => $filename
        ]);

        $this->trigger(self::EVENT_BEFORE_REPLACE_ASSET, $event);

        // Is the event preventing this from happening?
        if (!$event->isValid) {
            throw new ActionCancelledException(Craft::t('app', 'Something prevented the Asset file from being replaced.'));
        }

        $volume = $asset->getVolume();

        // Clear all thumb and transform data
        if (Image::isImageManipulatable($asset->getExtension())) {
            Craft::$app->getAssetTransforms()->deleteAllTransformData($asset);
        }

        // Open the stream for, uhh, streaming
        $stream = fopen($pathOnServer, 'rb');

        if (!$stream) {
            throw new FileException(Craft::t('app',
                'Could not open file for streaming at {path}',
                ['path' => $pathOnServer]));
        }

        // Re-use the same filename
        if (StringHelper::toLowerCase($asset->filename) == StringHelper::toLowerCase($filename)) {
            // The case is changing in the filename
            if ($asset->filename != $filename) {
                // Delete old, change the name, upload the new
                $volume->deleteFile($asset->getUri());
                $asset->newFilename = $filename;
                $volume->createFileByStream($asset->getUri(), $stream, []);
            } else {
                $volume->updateFileByStream($asset->getUri(), $stream, []);
            }
        } else {
            // Get an available name to avoid conflicts and upload the file
            $filename = $this->getNameReplacementInFolder($filename,
                $asset->folderId);

            // Delete old, change the name, upload the new
            $volume->deleteFile($asset->getUri());
            $asset->newFilename = $filename;
            $volume->createFileByStream($asset->getUri(), $stream, []);

            $asset->kind = AssetsHelper::getFileKindByExtension($filename);
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($asset->kind === 'image') {
            list ($asset->width, $asset->height) = Image::imageSize($pathOnServer);
        } else {
            $asset->width = null;
            $asset->height = null;
        }

        $asset->size = filesize($pathOnServer);
        $asset->dateModified = new DateTime('@'.filemtime($pathOnServer));

        $this->saveAsset($asset);

        $event = new ReplaceAssetEvent([
            'asset' => $asset,
            'filename' => $filename
        ]);
        $this->trigger(self::EVENT_AFTER_REPLACE_ASSET, $event);
    }

    /**
     * Rename an Asset.
     *
     * @param Asset $asset         The asset whose file should be renamed
     * @param bool  $runValidation Whether the filename should be validated
     *
     * @return bool Whether the asset was renamed successfully
     * @throws AssetLogicException if the asset’s volume is missing
     */
    public function renameFile(Asset $asset, bool $runValidation = true): bool
    {
        if ($runValidation && !$asset->validate(['newFilename'])) {
            Craft::info('Asset file not renamed due to validation error.', __METHOD__);

            return false;
        }

        $volume = $asset->getVolume();

        if (!$volume) {
            throw new AssetLogicException('Invalid volume ID: '.$asset->volumeId);
        }

        if (!$volume->renameFile($asset->getUri(), $asset->getUri($asset->newFilename))) {
            return false;
        }

        // Update the record
        $record = AssetRecord::findOne($asset->id);
        $record->filename = $asset->newFilename;
        $record->save(false);

        // Update the model
        $asset->filename = $asset->newFilename;
        $asset->newFilename = null;

        return true;
    }

    /**
     * Save an Asset folder.
     *
     * @param VolumeFolder $folder
     *
     * @throws AssetConflictException           If a folder already exists with such a name.
     * @throws AssetLogicException              If the parent folder is missing.
     * @throws VolumeObjectExistsException      If the file actually exists on the volume, but on in the index.
     * @return void
     */
    public function createFolder(VolumeFolder $folder)
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
            if ($folder->volumeId !== null) {
                throw $exception;
            }
        }
        $this->storeFolderRecord($folder);
    }

    /**
     * Rename a folder by it's id.
     *
     * @param int    $folderId
     * @param string $newName
     *
     * @throws AssetConflictException           If a folder already exists with such name in Assets Index
     * @throws AssetLogicException              If the folder to be renamed can't be found or trying to rename the top folder.
     * @throws VolumeObjectExistsException      If a folder already exists with such name in the Volume, but not in Index
     * @throws VolumeObjectNotFoundException    If the folder to be renamed can't be found in the Volume.
     * @return string The new folder name after cleaning it.
     */
    public function renameFolderById(int $folderId, string $newName): string
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
        $parentPath = dirname($folder->path);
        $newFullPath = ($parentPath && $parentPath !== '.' ? $parentPath.'/' : '').$newName.'/';

        foreach ($descendantFolders as $descendantFolder) {
            $descendantFolder->path = preg_replace('#^'.$folder->path.'#', $newFullPath.'/', $descendantFolder->path);
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
     * @param bool      $deleteFolder Should the folder be deleted along the record. Defaults to true.
     *
     * @throws VolumeException If deleting a single folder and it cannot be deleted.
     * @return void
     */
    public function deleteFoldersByIds($folderIds, bool $deleteFolder = true)
    {
        foreach ((array)$folderIds as $folderId) {
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
     * @param array $allowedVolumeIds
     * @param array $additionalCriteria additional criteria for filtering the tree
     *
     * @return array
     */
    public function getFolderTreeByVolumeIds($allowedVolumeIds, array $additionalCriteria = []): array
    {
        static $volumeFolders = [];

        $tree = [];

        // Get the tree for each source
        foreach ($allowedVolumeIds as $volumeId) {
            // Add additional criteria but prevent overriding volumeId and order.
            $criteria = array_merge($additionalCriteria, [
                'volumeId' => $volumeId,
                'order' => 'path'
            ]);
            $cacheKey = md5(Json::encode($criteria));

            // If this has not been yet fetched, fetch it.
            if (empty($volumeFolders[$cacheKey])) {
                $folders = $this->findFolders($criteria);
                $subtree = $this->_getFolderTreeByFolders($folders);
                $volumeFolders[$cacheKey] = reset($subtree);
            }

            $tree[$volumeId] = $volumeFolders[$cacheKey];
        }

        AssetsHelper::sortFolderTree($tree);

        return $tree;
    }

    /**
     * Get the folder tree for Assets by a folder id.
     *
     * @param int $folderId
     *
     * @return array
     */
    public function getFolderTreeByFolderId(int $folderId): array
    {
        if (($folder = $this->getFolderById($folderId)) === null) {
            return [];
        }

        return $this->_getFolderTreeByFolders([$folder]);
    }

    /**
     * Returns a folder by its ID.
     *
     * @param int $folderId
     *
     * @return VolumeFolder|null
     */
    public function getFolderById(int $folderId)
    {
        if ($this->_foldersById !== null && array_key_exists($folderId, $this->_foldersById)) {
            return $this->_foldersById[$folderId];
        }

        $result = $this->_createFolderQuery()
            ->where(['id' => $folderId])
            ->one();

        if (!$result) {
            return $this->_foldersById[$folderId] = null;
        }

        return $this->_foldersById[$folderId] = new VolumeFolder($result);
    }

    /**
     * Finds folders that match a given criteria.
     *
     * @param mixed $criteria
     *
     * @return VolumeFolder[]
     */
    public function findFolders($criteria = null): array
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->select([
                'id',
                'parentId',
                'volumeId',
                'name',
                'path',
            ])
            ->from(['{{%volumefolders}}']);

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
            $folder = new VolumeFolder($result);
            $this->_foldersById[$folder->id] = $folder;
            $folders[] = $folder;
        }

        return $folders;
    }

    /**
     * Returns all of the folders that are descendants of a given folder.
     *
     * @param VolumeFolder $parentFolder
     * @param string       $orderBy
     *
     * @return array
     */
    public function getAllDescendantFolders(VolumeFolder $parentFolder, string $orderBy = 'path'): array
    {
        /** @var $query Query */
        $query = (new Query())
            ->select([
                'id',
                'parentId',
                'volumeId',
                'name',
                'path',
            ])
            ->from(['{{%volumefolders}}'])
            ->where([
                'and',
                ['like', 'path', $parentFolder->path.'%', false],
                ['volumeId' => $parentFolder->volumeId],
                ['not', ['parentId' => null]]
            ]);

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        $results = $query->all();
        $descendantFolders = [];

        foreach ($results as $result) {
            $folder = new VolumeFolder($result);
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
     * @return VolumeFolder|null
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
     * Returns the root folder for a given volume ID.
     *
     * @param int $volumeId The volume ID
     *
     * @return VolumeFolder|null The root folder in that volume, or null if the volume doesn’t exist
     */
    public function getRootFolderByVolumeId(int $volumeId)
    {
        return $this->findFolder([
            'volumeId' => $volumeId,
            'parentId' => ':empty:'
        ]);
    }

    /**
     * Gets the total number of folders that match a given criteria.
     *
     * @param mixed $criteria
     *
     * @return int
     */
    public function getTotalFolders($criteria): int
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->from(['{{%volumefolders}}']);

        $this->_applyFolderConditions($query, $criteria);

        return (int)$query->count('[[id]]');
    }

    // File and folder managing
    // -------------------------------------------------------------------------


    /**
     * Get URL for a file.
     *
     * @param Asset                            $asset
     * @param AssetTransform|string|array|null $transform
     *
     * @return string
     */
    public function getUrlForAsset(Asset $asset, $transform = null): string
    {
        if ($transform === null || !Image::isImageManipulatable(pathinfo($asset->filename, PATHINFO_EXTENSION))) {
            $volume = $asset->getVolume();

            return AssetsHelper::generateUrl($volume, $asset);
        }

        // Get the transform index model
        $assetTransforms = Craft::$app->getAssetTransforms();
        $index = $assetTransforms->getTransformIndex($asset, $transform);

        // Does the file actually exist?
        if ($index->fileExists) {
            return $assetTransforms->getUrlForTransformByAssetAndTransformIndex($asset,
                $index);
        } else {
            if (Craft::$app->getConfig()->get('generateTransformsBeforePageLoad')) {
                try {
                    return $assetTransforms->ensureTransformUrlByIndexModel($index);
                } catch (ImageException $exception) {
                    Craft::warning($exception->getMessage(), __METHOD__);
                    $assetTransforms->deleteTransformIndex($index->id);

                    return UrlHelper::resourceUrl('404');
                }
            } else {
                // Queue up a new Generate Pending Transforms task, if there isn't one already
                $tasks = Craft::$app->getTasks();
                if (!$tasks->areTasksPending(GeneratePendingTransforms::class)) {
                    $tasks->createTask(GeneratePendingTransforms::class);
                }

                // Return the temporary transform URL
                return UrlHelper::resourceUrl('transforms/'.$index->id);
            }
        }
    }

    /**
     * Find a replacement for a filename
     *
     * @param string $originalFilename the original filename for which to find a replacement.
     * @param int    $folderId         THe folder in which to find the replacement
     *
     * @throws AssetLogicException If a suitable filename replacement cannot be found.
     * @return string
     */
    public function getNameReplacementInFolder(string $originalFilename, int $folderId): string
    {
        $folder = $this->getFolderById($folderId);
        if (!$folder) {
            throw new AssetLogicException();
        }
        $volume = $folder->getVolume();
        $fileList = $volume->getFileList((string)$folder->path, false);

        // Flip the array for faster lookup
        $existingFiles = [];

        foreach ($fileList as $file) {
            if (StringHelper::toLowerCase(rtrim($folder->path, '/')) == StringHelper::toLowerCase($file['dirname'])) {
                $existingFiles[StringHelper::toLowerCase($file['basename'])] = true;
            }
        }

        // Shorthand.
        $canUse = function($filenameToTest) use ($existingFiles) {
            return !isset($existingFiles[StringHelper::toLowerCase($filenameToTest)]);
        };

        if ($canUse($originalFilename)) {
            return $originalFilename;
        }

        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME);


        // If the file already ends with something that looks like a timestamp, use that instead.
        if (preg_match('/.*_(\d{6}_\d{6})$/', $filename, $matches)) {
            $base = $filename;
        } else {
            $timestamp = DateTimeHelper::currentUTCDateTime()->format('ymd_His');
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
     * @param Asset  $asset
     * @param int    $folderId    Id of the folder of the destination
     * @param string $newFilename filename to use for the file at it's destination
     *
     * @throws AssetDisallowedExtensionException If the extension is not allowed.
     * @throws AssetConflictException            If there is a conflict.
     * @throws AssetLogicException               If the target folder does not exist.
     * @return void
     */
    public function moveAsset(Asset $asset, int $folderId, string $newFilename = '')
    {
        $filename = $newFilename ?: $asset->filename;

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (!Craft::$app->getConfig()->isExtensionAllowed($extension)) {
            throw new AssetDisallowedExtensionException(Craft::t('app',
                'The extension “{extension}” is not allowed.',
                ['extension' => $extension]));
        }

        $existingAsset = Asset::find()
            ->folderId($folderId)
            ->filename(Db::escapeParam($filename))
            ->one();

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

        $this->_moveAssetFileToFolder($asset, $targetFolder, $filename);

        $asset->folderId = $folderId;
        $asset->volumeId = $targetFolder->volumeId;
        $asset->filename = $filename;

        $this->saveAsset($asset);
    }


    /**
     * Ensure a folder entry exists in the DB for the full path and return it's id.
     *
     * @param string $fullPath The path to ensure the folder exists at.
     * @param int    $volumeId
     *
     * @return int
     */
    public function ensureFolderByFullPathAndVolumeId(string $fullPath, int $volumeId): int
    {
        $parameters = new FolderCriteria([
            'path' => $fullPath,
            'volumeId' => $volumeId
        ]);

        if (($folderModel = $this->findFolder($parameters)) !== null) {
            return $folderModel->id;
        }

        // If we don't have a folder matching these, create a new one
        $parts = explode('/', rtrim($fullPath, '/'));
        $folderName = array_pop($parts);

        if (empty($parts)) {
            // Looking for a top level folder, apparently.
            $parameters->path = '';
            $parameters->parentId = ':empty:';
        } else {
            $parameters->path = implode('/', $parts).'/';
        }

        // Look up the parent folder
        $parentFolder = $this->findFolder($parameters);

        if ($parentFolder === null) {
            $parentId = ':empty:';
        } else {
            $parentId = $parentFolder->id;
        }

        $folderModel = new VolumeFolder();
        $folderModel->volumeId = $volumeId;
        $folderModel->parentId = $parentId;
        $folderModel->name = $folderName;
        $folderModel->path = $fullPath;

        $this->storeFolderRecord($folderModel);

        return $folderModel->id;
    }

    /**
     * Store a folder by model
     *
     * @param VolumeFolder $folder
     *
     * @return void
     */
    public function storeFolderRecord(VolumeFolder $folder)
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
     * @param User|null $userModel
     *
     * @return VolumeFolder|null
     */
    public function getUserFolder(User $userModel = null)
    {
        $sourceTopFolder = $this->findFolder([
            'volumeId' => ':empty:',
            'parentId' => ':empty:'
        ]);

        // Unlikely, but would be very awkward if this happened without any contingency plans in place.
        if (!$sourceTopFolder) {
            $sourceTopFolder = new VolumeFolder();
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
            $folder = new VolumeFolder();
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
     * @param Asset        $asset
     * @param VolumeFolder $targetFolder
     * @param string       $newFilename new filename to use
     *
     * @throws FileException If there was a problem with the actual file.
     * @return void
     */
    private function _moveAssetFileToFolder(Asset $asset, VolumeFolder $targetFolder, string $newFilename = '')
    {
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
                /** @var AssetTransformIndex $transformIndex */
                $fromTransformPath = $assetTransforms->getTransformSubpath($asset,
                    $transformIndex);
                $toTransformPath = $fromTransformPath;

                // In case we're changing the filename, make sure that we're not missing that.
                $parts = explode('/', $toTransformPath);
                $transformName = array_pop($parts);
                $toTransformPath = implode('/', $parts).'/'.pathinfo($filename, PATHINFO_FILENAME).'.'.pathinfo($transformName, PATHINFO_EXTENSION);

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
        } // Move between volumes
        else {
            $tempFilename = uniqid(pathinfo($asset->filename, PATHINFO_FILENAME), true).'.'.$asset->getExtension();
            $tempPath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$tempFilename;
            $sourceVolume->saveFileLocally($asset->getUri(), $tempPath);
            $targetVolume = $targetFolder->getVolume();
            $stream = fopen($tempPath, 'rb');

            if (!$stream) {
                FileHelper::removeFile($tempPath);
                throw new FileException(Craft::t('app',
                    'Could not open file for streaming at {path}',
                    ['path' => $asset->newFilePath]));
            }

            $targetVolume->createFileByStream($toPath, $stream, []);
            $sourceVolume->deleteFile($asset->getUri());

            if (is_resource($stream)) {
                fclose($stream);
            }

            FileHelper::removeFile($tempPath);

            // Nuke the transforms
            $assetTransforms->deleteAllTransformData($asset);
        }
    }

    /**
     * Returns a DbCommand object prepped for retrieving assets.
     *
     * @return Query
     */
    private function _createFolderQuery(): Query
    {
        return (new Query())
            ->select(['id', 'parentId', 'volumeId', 'name', 'path'])
            ->from(['{{%volumefolders}}']);
    }

    /**
     * Return the folder tree form a list of folders.
     *
     * @param VolumeFolder[] $folders
     *
     * @return array
     */
    private function _getFolderTreeByFolders(array $folders): array
    {
        $tree = [];
        $referenceStore = [];

        foreach ($folders as $folder) {
            // We'll be adding all of the children in this loop, anyway, so we set
            // the children list to an empty array so that folders that have no children don't
            // trigger any queries, when asked for children
            $folder->setChildren([]);
            if ($folder->parentId && isset($referenceStore[$folder->parentId])) {
                $referenceStore[$folder->parentId]->addChild($folder);
            } else {
                $tree[] = $folder;
            }

            $referenceStore[$folder->id] = $folder;
        }

        return $tree;
    }

    /**
     * Applies WHERE conditions to a DbCommand query for folders.
     *
     * @param Query          $query
     * @param FolderCriteria $criteria
     *
     * @return void
     */
    private function _applyFolderConditions(Query $query, FolderCriteria $criteria)
    {
        if ($criteria->id) {
            $query->andWhere(Db::parseParam('id', $criteria->id));
        }

        if ($criteria->volumeId) {
            $query->andWhere(Db::parseParam('volumeId', $criteria->volumeId));
        }

        if ($criteria->parentId) {
            $query->andWhere(Db::parseParam('parentId', $criteria->parentId));
        }

        if ($criteria->name) {
            $query->andWhere(Db::parseParam('name', $criteria->name));
        }

        if ($criteria->path !== null) {
            // Does the path have a comma in it?
            if (strpos($criteria->path, ',') !== false) {
                // Escape the comma.
                $query->andWhere(Db::parseParam('path', str_replace(',', '\,', $criteria->path)));
            } else {
                $query->andWhere(Db::parseParam('path', $criteria->path));
            }
        }
    }
}
