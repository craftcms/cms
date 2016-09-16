<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\AssetConflictException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\AssetException;
use craft\app\errors\UploadFailedException;
use craft\app\fields\Assets as AssetsField;
use craft\app\helpers\Assets;
use craft\app\helpers\Io;
use craft\app\elements\Asset;
use craft\app\helpers\StringHelper;
use craft\app\models\VolumeFolder;
use craft\app\web\Controller;
use craft\app\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note that all actions in the controller except [[actionGenerateTransform]] require an authenticated Craft session
 * via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
// TODO: permission rework
// TODO: All exceptions must be translatable.
class AssetsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['generate-transform'];

    // Public Methods
    // =========================================================================

    /**
     * Upload a file
     *
     * @return Response
     * @throws BadRequestHttpException for reasons
     */
    public function actionSaveAsset()
    {
        $this->requireAcceptsJson();

        $uploadedFile = UploadedFile::getInstanceByName('assets-upload');
        $request = Craft::$app->getRequest();
        $assetId = $request->getBodyParam('assetId');
        $folderId = $request->getBodyParam('folderId');
        $fieldId = $request->getBodyParam('fieldId');
        $elementId = $request->getBodyParam('elementId');
        $conflictResolution = $request->getBodyParam('userResponse');

        $newFile = (bool)$uploadedFile && empty($assetId);
        $resolveConflict = !empty($conflictResolution) && !empty($assetId);

        try {
            // Resolving a conflict?
            $assets = Craft::$app->getAssets();
            if ($resolveConflict) {
                // When resolving a conflict, $assetId is the id of the file that was created
                // and is conflicting with an existing file.
                if ($conflictResolution == 'replace') {
                    $assetToReplaceWith = $assets->getAssetById($assetId);
                    $filename = Assets::prepareAssetName($request->getRequiredBodyParam('filename'));
                    $assetToReplace = $assets->findAsset([
                        'filename' => $filename,
                        'folderId' => $assetToReplaceWith->folderId
                    ]);

                    if (!$assetToReplace) {
                        throw new BadRequestHttpException('Asset to be replaced cannot be found');
                    }

                    // Check if the user has the permissions to delete files
                    $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume',
                        $assetToReplace);

                    if ($assetToReplace->volumeId != $assetToReplaceWith->volumeId) {
                        throw new BadRequestHttpException('Asset to be replaced does not live in the same volume as its replacement');
                    }

                    $assets->replaceAsset($assetToReplace,
                        $assetToReplaceWith);
                } else {
                    if ($conflictResolution == 'cancel') {
                        $assetToDelete = $assets->getAssetById($assetId);

                        if ($assetToDelete) {
                            $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume',
                                $assetToDelete);
                            $assets->deleteAssetsByIds($assetId);
                        }
                    }
                }

                return $this->asJson(['success' => true]);
            }

            if ($newFile) {
                if ($uploadedFile->hasError) {
                    throw new UploadFailedException($uploadedFile->error);
                }

                if (empty($folderId) && (empty($fieldId) || empty($elementId))) {
                    throw new BadRequestHttpException('No target destination provided for uploading');
                }

                if (empty($folderId)) {
                    $field = Craft::$app->getFields()->getFieldById($fieldId);

                    if (!($field instanceof AssetsField)) {
                        throw new BadRequestHttpException('The field provided is not an Assets field');
                    }

                    $element = $elementId ? Craft::$app->getElements()->getElementById($elementId) : null;
                    $folderId = $field->resolveDynamicPathToFolderId($element);
                }

                if (empty($folderId)) {
                    throw new BadRequestHttpException('The target destination provided for uploading is not valid');
                }

                $folder = $assets->findFolder(['id' => $folderId]);

                if (!$folder) {
                    throw new BadRequestHttpException('The target folder provided for uploading is not valid');
                }

                // Check the permissions to upload in the resolved folder.
                $this->_requirePermissionByFolder('saveAssetInVolume',
                    $folder);

                $pathOnServer = Io::getTempFilePath($uploadedFile->name);
                $result = $uploadedFile->saveAs($pathOnServer);

                if (!$result) {
                    Io::deleteFile($pathOnServer, true);
                    throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
                }

                $filename = Assets::prepareAssetName($uploadedFile->name);

                $asset = new Asset();

                // Make sure there are no double spaces, if the filename had a space followed by a
                // capital letter because of Yii's "word" logic.
                $asset->title = str_replace('  ', ' ', StringHelper::toTitleCase(Io::getFilename($filename, false)));

                $asset->newFilePath = $pathOnServer;
                $asset->filename = $filename;
                $asset->folderId = $folder->id;
                $asset->volumeId = $folder->volumeId;

                try {
                    $assets->saveAsset($asset);
                    Io::deleteFile($pathOnServer, true);
                } catch (AssetConflictException $exception) {
                    // Okay, get a replacement name and re-save Asset.
                    $replacementName = $assets->getNameReplacementInFolder($asset->filename,
                        $folder->id);
                    $asset->filename = $replacementName;

                    $assets->saveAsset($asset);
                    Io::deleteFile($pathOnServer, true);

                    return $this->asJson([
                        'prompt' => true,
                        'assetId' => $asset->id,
                        'filename' => $uploadedFile->name
                    ]);
                } // No matter what happened, delete the file on server.
                catch (\Exception $exception) {
                    Io::deleteFile($pathOnServer, true);
                    throw $exception;
                }

                return $this->asJson([
                    'success' => true,
                    'filename' => $asset->filename
                ]);
            } else {
                throw new BadRequestHttpException('Not a new asset');
            }
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }
    }

    /**
     * Replace a file
     *
     * @return Response
     */
    public function actionReplaceFile()
    {
        $this->requireAcceptsJson();
        $assetId = Craft::$app->getRequest()->getBodyParam('fileId');
        $uploadedFile = UploadedFile::getInstanceByName('replaceFile');

        $assets = Craft::$app->getAssets();
        $asset = $assets->getAssetById($assetId);

        // Check if we have the relevant permissions.
        $this->_requirePermissionByAsset('saveAssetInVolume', $asset);
        $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume',
            $asset);

        try {
            if ($uploadedFile->hasError) {
                throw new UploadFailedException($uploadedFile->error);
            }

            $fileName = Assets::prepareAssetName($uploadedFile->name);
            $pathOnServer = Io::getTempFilePath($uploadedFile->name);
            $result = $uploadedFile->saveAs($pathOnServer);

            if (!$result) {
                Io::deleteFile($pathOnServer, true);
                throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
            }

            $assets->replaceAssetFile($asset, $pathOnServer,
                $fileName);
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true, 'assetId' => $assetId]);
    }

    /**
     * Create a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the parent folder cannot be found
     */
    public function actionCreateFolder()
    {
        $this->requireLogin();
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $parentId = $request->getRequiredBodyParam('parentId');
        $folderName = $request->getRequiredBodyParam('folderName');
        $folderName = Assets::prepareAssetName($folderName, false);

        $assets = Craft::$app->getAssets();
        $parentFolder = $assets->findFolder(['id' => $parentId]);

        if (!$parentFolder) {
            throw new BadRequestHttpException('The parent folder cannot be found');
        }

        // Check if it's possible to create subfolders in target Volume.
        $this->_requirePermissionByFolder('createFoldersInVolume',
            $parentFolder);

        try {
            $folderModel = new VolumeFolder();
            $folderModel->name = $folderName;
            $folderModel->parentId = $parentId;
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path.$folderName.'/';

            $assets->createFolder($folderModel);

            return $this->asJson([
                'success' => true,
                'folderName' => $folderModel->name,
                'folderId' => $folderModel->id
            ]);
        } catch (AssetException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }
    }

    /**
     * Delete a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     */
    public function actionDeleteFolder()
    {
        $this->requireLogin();
        $this->requireAcceptsJson();
        $folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');

        $assets = Craft::$app->getAssets();
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->_requirePermissionByFolder('deleteFilesAndFoldersInVolume',
            $folder);
        try {
            $assets->deleteFoldersByIds($folderId);
        } catch (AssetException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Rename a folder
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     */
    public function actionRenameFolder()
    {
        $this->requireLogin();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $assets = Craft::$app->getAssets();
        $folderId = $request->getRequiredBodyParam('folderId');
        $newName = $request->getRequiredBodyParam('newName');
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects and create folders in target Volume.
        $this->_requirePermissionByFolder('deleteFilesAndFolders', $folder);
        $this->_requirePermissionByFolder('createFolders', $folder);

        try {
            $newName = Craft::$app->getAssets()->renameFolderById($folderId,
                $newName);
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true, 'newName' => $newName]);
    }


    /**
     * Move an Asset or multiple Assets.
     *
     * @return Response
     * @throws BadRequestHttpException if the asset or the target folder cannot be found
     */
    public function actionMoveAsset()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $assetId = $request->getRequiredBodyParam('fileId');
        $folderId = $request->getBodyParam('folderId');
        $filename = $request->getBodyParam('filename');
        $conflictResolution = $request->getBodyParam('userResponse');

        $assets = Craft::$app->getAssets();
        $asset = $assets->getAssetById($assetId);
        $folder = $assets->getFolderById($folderId);

        if (empty($asset)) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        if (empty($folder)) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in source Volume and save Assets in target Volume.
        $this->_requirePermissionByAsset('deleteFilesAndFolders', $asset);
        $this->_requirePermissionByFolder('saveAssetInVolume', $folder);

        try {

            if (!empty($filename)) {
                $assets->renameAsset($asset, $filename);

                return $this->asJson(['success' => true]);
            }

            if ($asset->folderId != $folderId) {
                if (!empty($conflictResolution)) {
                    $conflictingAsset = $assets->findAsset([
                        'filename' => $asset->filename,
                        'folderId' => $folderId
                    ]);

                    if ($conflictResolution == 'replace') {
                        $assets->replaceAsset($conflictingAsset,
                            $asset, true);
                    } else {
                        if ($conflictResolution == 'keepBoth') {
                            $newFilename = $assets->getNameReplacementInFolder($asset->filename,
                                $folderId);
                            $assets->moveAsset($asset,
                                $folderId, $newFilename);
                        }
                    }
                } else {
                    try {
                        $assets->moveAsset($asset,
                            $folderId);
                    } catch (AssetConflictException $exception) {
                        return $this->asJson([
                            'prompt' => true,
                            'filename' => $asset->filename,
                            'assetId' => $asset->id
                        ]);
                    }
                }
            }
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Move a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder to move, or the destination parent folder, cannot be found
     */
    public function actionMoveFolder()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $folderBeingMovedId = $request->getRequiredBodyParam('folderId');
        $newParentFolderId = $request->getRequiredBodyParam('parentId');
        $conflictResolution = $request->getBodyParam('userResponse');

        $assets = Craft::$app->getAssets();
        $folderToMove = $assets->getFolderById($folderBeingMovedId);
        $destinationFolder = $assets->getFolderById($newParentFolderId);

        if (empty($folderToMove)) {
            throw new BadRequestHttpException('The folder you are trying to move does not exist');
        }

        if (empty($destinationFolder)) {
            throw new BadRequestHttpException('The destination folder does not exist');
        }

        // Check if it's possible to delete objects in source Volume, create folders
        // in target Volume and save Assets in target Volume.
        $this->_requirePermissionByFolder('deleteFilesAndFolders',
            $folderToMove);
        $this->_requirePermissionByFolder('createSubfoldersInAssetSource',
            $destinationFolder);
        $this->_requirePermissionByFolder('saveAssetInVolume',
            $destinationFolder);

        try {
            $removeFromTree = [];

            $sourceTree = $assets->getAllDescendantFolders($folderToMove);

            if (empty($conflictResolution)) {
                $existingFolder = $assets->findFolder([
                    'parentId' => $newParentFolderId,
                    'name' => $folderToMove->name
                ]);

                if ($existingFolder) {
                    // Throw a prompt
                    return $this->asJson([
                        'prompt' => true,
                        'foldername' => $folderToMove->name,
                        'folderId' => $folderBeingMovedId,
                        'parentId' => $newParentFolderId
                    ]);
                } else {
                    // No conflicts, mirror the existing structure
                    $folderIdChanges = Assets::mirrorFolderStructure($folderToMove,
                        $destinationFolder);

                    // Get the file transfer list.
                    $allSourceFolderIds = array_keys($sourceTree);
                    $allSourceFolderIds[] = $folderBeingMovedId;
                    $foundAssets = $assets->findAssets(['folderId' => $allSourceFolderIds]);
                    $fileTransferList = Assets::getFileTransferList($foundAssets,
                        $folderIdChanges, $conflictResolution == 'merge');
                }
            } else {
                // Resolving a confclit
                $existingFolder = $assets->findFolder([
                    'parentId' => $newParentFolderId,
                    'name' => $folderToMove->name
                ]);
                $targetTreeMap = [];

                // When merging folders, make sure that we're not overwriting folders
                if ($conflictResolution == 'merge') {
                    $targetTree = $assets->getAllDescendantFolders($existingFolder);
                    $targetPrefixLength = strlen($destinationFolder->path);
                    $targetTreeMap = [];

                    foreach ($targetTree as $existingFolder) {
                        $targetTreeMap[substr($existingFolder->path,
                            $targetPrefixLength)] = $existingFolder->id;
                    }

                    $removeFromTree = [$existingFolder->id];
                } // When replacing, just nuke everything that's in our way
                else {
                    if ($conflictResolution == 'replace') {
                        $removeFromTree = [$existingFolder->id];
                        $assets->deleteFoldersByIds($existingFolder->id);
                    }
                }

                // Mirror the structure, passing along the exsting folder map
                $folderIdChanges = Assets::mirrorFolderStructure($folderToMove,
                    $destinationFolder, $targetTreeMap);

                // Get file transfer list for the progress bar
                $allSourceFolderIds = array_keys($sourceTree);
                $allSourceFolderIds[] = $folderBeingMovedId;
                $foundAssets = $assets->findAssets(['folderId' => $allSourceFolderIds]);
                $fileTransferList = Assets::getFileTransferList($foundAssets,
                    $folderIdChanges, $conflictResolution == 'merge');
            }
        } catch (AssetLogicException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson([
            'success' => true,
            'changedIds' => $folderIdChanges,
            'transferList' => $fileTransferList,
            'removeFromTree' => $removeFromTree
        ]);
    }

    /**
     * Download a file.
     *
     * @return Response
     * @throws BadRequestHttpException if the file to download cannot be found.
     */
    public function actionDownloadAsset()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $assetId = Craft::$app->getRequest()->getRequiredBodyParam('assetId');
        $assetService = Craft::$app->getAssets();

        $asset = $assetService->getAssetById($assetId);
        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The Asset you\'re trying to download does not exist.'));
        }

        $this->_requirePermissionByAsset("viewAssetSource", $asset);

        // All systems go, engage hyperdrive! (so PHP doesn't interrupt our stream)
        Craft::$app->getConfig()->maxPowerCaptain();
        $localPath = $asset->getCopyOfFile();

        Craft::$app->getResponse()->sendFile($localPath, $asset->filename, false);
        Io::deleteFile($localPath);
        Craft::$app->end();
    }

    /**
     * Generate a transform.
     *
     * @return Response
     */
    public function actionGenerateTransform()
    {
        $request = Craft::$app->getRequest();
        $transformId = $request->getQueryParam('transformId');
        $returnUrl = (bool)$request->getBodyParam('returnUrl',
            false);

        // If transform Id was not passed in, see if file id and handle were.
        $assetTransforms = Craft::$app->getAssetTransforms();

        if (empty($transformId)) {
            $assetId = $request->getBodyParam('fileId');
            $handle = $request->getBodyParam('handle');
            $assetModel = Craft::$app->getAssets()->getAssetById($assetId);
            $transformIndexModel = $assetTransforms->getTransformIndex($assetModel,
                $handle);
        } else {
            $transformIndexModel = $assetTransforms->getTransformIndexModelById($transformId);
        }

        $url = $assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);

        if ($returnUrl) {
            return $this->asJson(['url' => $url]);
        }

        return $this->redirect($url);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param Asset  $asset          Asset on the Volume on which to require the permission.
     *
     * @return void
     */
    private function _requirePermissionByAsset($permissionName, Asset $asset)
    {
        $this->_requirePermissionByVolumeId($permissionName, $asset->volumeId);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string       $permissionName Name of the permission to require.
     * @param VolumeFolder $folder         Folder on the Volume on which to require the permission.
     *
     * @return void
     */
    private function _requirePermissionByFolder($permissionName, VolumeFolder $folder)
    {
        $this->_requirePermissionByVolumeId($permissionName, $folder->volumeId);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param int    $volumeId       The Volume id on which to require the permission.
     *
     * @return void
     */
    private function _requirePermissionByVolumeId($permissionName, $volumeId)
    {
        $this->requirePermission($permissionName.':'.$volumeId);
    }
}

