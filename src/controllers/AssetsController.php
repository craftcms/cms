<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Volume;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\AssetLogicException;
use craft\errors\UploadFailedException;
use craft\fields\Assets as AssetsField;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Path as PathHelper;
use craft\helpers\StringHelper;
use craft\image\Raster;
use craft\models\VolumeFolder;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 * Note that all actions in the controller except for [[actionGenerateTransform()]] and [[actionGenerateThumb()]]
 * require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['generate-thumb', 'generate-transform'];

    // Public Methods
    // =========================================================================

    /**
     * Upload a file
     *
     * @return Response
     * @throws BadRequestHttpException for reasons
     */
    public function actionSaveAsset(): Response
    {
        $uploadedFile = UploadedFile::getInstanceByName('assets-upload');
        $request = Craft::$app->getRequest();
        $folderId = $request->getBodyParam('folderId');
        $fieldId = $request->getBodyParam('fieldId');
        $elementId = $request->getBodyParam('elementId');

        if (empty($folderId) && (empty($fieldId) || empty($elementId))) {
            throw new BadRequestHttpException('No target destination provided for uploading');
        }

        if ($uploadedFile === null) {
            throw new BadRequestHttpException('No file was uploaded');
        }

        try {
            $assets = Craft::$app->getAssets();

            $tempPath = $this->_getUploadedFileTempPath($uploadedFile);

            if (empty($folderId)) {
                $field = Craft::$app->getFields()->getFieldById((int)$fieldId);

                if (!($field instanceof AssetsField)) {
                    throw new BadRequestHttpException('The field provided is not an Assets field');
                }

                $element = $elementId ? Craft::$app->getElements()->getElementById((int)$elementId) : null;
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
            $this->_requirePermissionByFolder('saveAssetInVolume', $folder);

            $filename = Assets::prepareAssetName($uploadedFile->name);

            $asset = new Asset();
            $asset->tempFilePath = $tempPath;
            $asset->filename = $filename;
            $asset->newFolderId = $folder->id;
            $asset->volumeId = $folder->volumeId;
            $asset->avoidFilenameConflicts = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);

            $result = Craft::$app->getElements()->saveElement($asset);

            // In case of error, let user know about it.
            if (!$result) {
                $errors = $asset->getFirstErrors();
                return $this->asErrorJson(Craft::t('app', 'Failed to save the Asset:') . implode(";\n", $errors));
            }

            if ($asset->conflictingFilename !== null) {
                $conflictingAsset = Asset::findOne(['folderId' => $folder->id, 'filename' => $asset->conflictingFilename]);

                return $this->asJson([
                    'conflict' => Craft::t('app', 'A file with the name “{filename}” already exists.', ['filename' => $asset->conflictingFilename]),
                    'assetId' => $asset->id,
                    'filename' => $asset->conflictingFilename,
                    'conflictingAssetId' => $conflictingAsset ? $conflictingAsset->id : null
                ]);
            }

            return $this->asJson([
                'success' => true,
                'filename' => $asset->filename,
                'assetId' => $asset->id
            ]);
        } catch (\Throwable $e) {
            Craft::error('An error occurred when saving an asset: ' . $e->getMessage(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * Replace a file
     *
     * @return Response
     * @throws BadRequestHttpException if incorrect combination of parameters passed.
     * @throws NotFoundHttpException if Asset cannot be found by id.
     */
    public function actionReplaceFile(): Response
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $assetId = $request->getBodyParam('assetId');

        $sourceAssetId = $request->getBodyParam('sourceAssetId');
        $targetFilename = $request->getBodyParam('targetFilename');
        $uploadedFile = UploadedFile::getInstanceByName('replaceFile');

        $assets = Craft::$app->getAssets();

        // Must have at least one existing Asset (source or target).
        // Must have either target Asset or target file name.
        // Must have either uploaded file or source Asset.
        if ((empty($assetId) && empty($sourceAssetId)) ||
            (empty($assetId) && empty($targetFilename)) ||
            ($uploadedFile === null && empty($sourceAssetId))
        ) {
            throw new BadRequestHttpException('Incorrect combination of parameters.');
        }

        $sourceAsset = null;
        $assetToReplace = null;

        if ($assetId && !$assetToReplace = $assets->getAssetById($assetId)) {
            throw new NotFoundHttpException('Asset not found.');
        }

        if ($sourceAssetId && !$sourceAsset = $assets->getAssetById($sourceAssetId)) {
            throw new NotFoundHttpException('Asset not found.');
        }

        $this->_requirePermissionByAsset('saveAssetInVolume', $assetToReplace ?: $sourceAsset);
        $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume', $assetToReplace ?: $sourceAsset);

        try {
            // Handle the Element Action
            if (!empty($assetToReplace) && $uploadedFile) {
                $tempPath = $this->_getUploadedFileTempPath($uploadedFile);
                $filename = Assets::prepareAssetName($uploadedFile->name);
                $assets->replaceAssetFile($assetToReplace, $tempPath, $filename);
            } else if (!empty($sourceAsset)) {
                // Or replace using an existing Asset

                // See if we can find an Asset to replace.
                if (empty($assetToReplace)) {
                    // Make sure the extension didn't change
                    if (pathinfo($targetFilename, PATHINFO_EXTENSION) !== $sourceAsset->getExtension()) {
                        throw new Exception($targetFilename . ' doesn\'t have the original file extension.');
                    }

                    $assetToReplace = Asset::find()
                        ->select(['elements.id'])
                        ->folderId($sourceAsset->folderId)
                        ->filename(Db::escapeParam($targetFilename))
                        ->one();
                }

                // If we have an actual asset for which to replace the file, just do it.
                if (!empty($assetToReplace)) {
                    $tempPath = $sourceAsset->getCopyOfFile();
                    $assets->replaceAssetFile($assetToReplace, $tempPath, $assetToReplace->filename);
                    Craft::$app->getElements()->deleteElement($sourceAsset);
                } else {
                    // If all we have is the filename, then make sure that the destination is empty and go for it.
                    $volume = $sourceAsset->getVolume();
                    $volume->deleteFile(rtrim($sourceAsset->folderPath, '/') . '/' . $targetFilename);
                    $sourceAsset->newFilename = $targetFilename;
                    // Don't validate required custom fields
                    Craft::$app->getElements()->saveElement($sourceAsset);
                    $assetId = $sourceAsset->id;
                }
            }
        } catch (\Throwable $e) {
            Craft::error('An error occurred when replacing an asset: ' . $e->getMessage(), __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson($e->getMessage());
        }

        return $this->asJson(['success' => true, 'assetId' => $assetId]);
    }

    /**
     * Create a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the parent folder cannot be found
     */
    public function actionCreateFolder(): Response
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
            $folderModel->path = $parentFolder->path . $folderName . '/';

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
    public function actionDeleteFolder(): Response
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
     * Delete an Asset.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     */
    public function actionDeleteAsset(): Response
    {
        $this->requireLogin();
        $this->requireAcceptsJson();
        $assets = Craft::$app->getAssets();

        $assetId = Craft::$app->getRequest()->getRequiredBodyParam('assetId');
        $asset = $assets->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException('The asset cannot be found');
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume', $asset);

        try {
            Craft::$app->getElements()->deleteElement($asset);
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
    public function actionRenameFolder(): Response
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
        $this->_requirePermissionByFolder('deleteFilesAndFoldersInVolume', $folder);
        $this->_requirePermissionByFolder('createFoldersInVolume', $folder);

        try {
            $newName = Craft::$app->getAssets()->renameFolderById($folderId,
                $newName);
        } catch (\Throwable $exception) {
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
    public function actionMoveAsset(): Response
    {
        $this->requireLogin();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $assetsService = Craft::$app->getAssets();

        // Get the asset
        $assetId = $request->getRequiredBodyParam('assetId');
        $asset = $assetsService->getAssetById($assetId);

        if (empty($asset)) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        // Get the target folder
        $folderId = $request->getBodyParam('folderId', $asset->folderId);
        $folder = $assetsService->getFolderById($folderId);

        if (empty($folder)) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Get the target filename
        $filename = $request->getBodyParam('filename', $asset->filename);

        // Check if it's possible to delete objects in source Volume and save Assets in target Volume.
        $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume', $asset);
        $this->_requirePermissionByFolder('saveAssetInVolume', $folder);

        if ($request->getBodyParam('force')) {
            // Check for a conflicting Asset
            $conflictingAsset = Asset::find()
                ->select(['elements.id'])
                ->folderId($folderId)
                ->filename(Db::escapeParam($asset->filename))
                ->one();

            // If there's an Asset conflicting, then merge and replace file.
            if ($conflictingAsset) {
                Craft::$app->getElements()->mergeElementsByIds($conflictingAsset->id, $asset->id);
            } else {
                $volume = $folder->getVolume();
                $volume->deleteFile(rtrim($folder->path, '/') . '/' . $asset->filename);
            }
        }

        $result = $assetsService->moveAsset($asset, $folder, $filename);

        if (!$result) {
            // Get the corrected filename
            list(, $filename) = Assets::parseFileLocation($asset->newLocation);

            return $this->asJson([
                'conflict' => $asset->getFirstError('newLocation'),
                'suggestedFilename' => $asset->suggestedFilename,
                'filename' => $filename,
                'assetId' => $asset->id
            ]);
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Move a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder to move, or the destination parent folder, cannot be found
     */
    public function actionMoveFolder(): Response
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $folderBeingMovedId = $request->getRequiredBodyParam('folderId');
        $newParentFolderId = $request->getRequiredBodyParam('parentId');
        $force = $request->getBodyParam('force', false);
        $merge = !$force ? $request->getBodyParam('merge', false) : false;

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
        $this->_requirePermissionByFolder('deleteFilesAndFoldersInVolume', $folderToMove);
        $this->_requirePermissionByFolder('createFoldersInVolume', $destinationFolder);
        $this->_requirePermissionByFolder('saveAssetInVolume', $destinationFolder);

        $targetVolume = $destinationFolder->getVolume();

        $existingFolder = $assets->findFolder([
            'parentId' => $newParentFolderId,
            'name' => $folderToMove->name
        ]);

        if (!$existingFolder) {
            $existingFolder = $targetVolume->folderExists(rtrim($destinationFolder->path, '/') . '/' . $folderToMove->name);
        }

        // If this a conflict and no force or merge flags were passed in then STOP RIGHT THERE!
        if ($existingFolder && !$force && !$merge) {
            // Throw a prompt
            return $this->asJson([
                'conflict' => Craft::t('app', 'Folder “{folder}” already exists at target location', ['folder' => $folderToMove->name]),
                'folderId' => $folderBeingMovedId,
                'parentId' => $newParentFolderId
            ]);
        }

        try {
            $sourceTree = $assets->getAllDescendantFolders($folderToMove);

            if (!$existingFolder) {
                // No conflicts, mirror the existing structure
                $folderIdChanges = Assets::mirrorFolderStructure($folderToMove, $destinationFolder);

                // Get the file transfer list.
                $allSourceFolderIds = array_keys($sourceTree);
                $allSourceFolderIds[] = $folderBeingMovedId;
                $foundAssets = Asset::find()
                    ->folderId($allSourceFolderIds)
                    ->all();
                $fileTransferList = Assets::fileTransferList($foundAssets, $folderIdChanges);
            } else {
                $targetTreeMap = [];

                // If an indexed folder is conflicting
                if ($existingFolder instanceof VolumeFolder) {
                    // Delete if using dforce
                    if ($force) {
                        $assets->deleteFoldersByIds($existingFolder->id);
                    } else {
                        // Or build a map of existing folders for file move
                        $targetTree = $assets->getAllDescendantFolders($existingFolder);
                        $targetPrefixLength = strlen($destinationFolder->path);

                        foreach ($targetTree as $existingFolder) {
                            $targetTreeMap[substr($existingFolder->path,
                                $targetPrefixLength)] = $existingFolder->id;
                        }
                    }
                } else if ($existingFolder && $force) {
                    // An un-indexed folder is conflicting. If we're forcing things, just remove it.
                    $targetVolume->deleteDir(rtrim($destinationFolder->path, '/') . '/' . $folderToMove->name);
                }

                // Mirror the structure, passing along the exsting folder map
                $folderIdChanges = Assets::mirrorFolderStructure($folderToMove, $destinationFolder, $targetTreeMap);

                // Get file transfer list for the progress bar
                $allSourceFolderIds = array_keys($sourceTree);
                $allSourceFolderIds[] = $folderBeingMovedId;
                $foundAssets = Asset::find()
                    ->folderId($allSourceFolderIds)
                    ->all();
                $fileTransferList = Assets::fileTransferList($foundAssets, $folderIdChanges);
            }
        } catch (AssetLogicException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson([
            'success' => true,
            'transferList' => $fileTransferList,
            'newFolderId' => $folderIdChanges[$folderBeingMovedId] ?? null
        ]);
    }

    /**
     * Return the image editor template.
     *
     * @return Response
     * @throws BadRequestHttpException if the Asset is missing.
     */
    public function actionImageEditor(): Response
    {
        $assetId = Craft::$app->getRequest()->getRequiredBodyParam('assetId');
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The Asset you’re trying to edit does not exist.'));
        }

        $focal = $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null;

        $html = $this->getView()->renderTemplate('_special/image_editor');

        return $this->asJson(['html' => $html, 'focalPoint' => $focal]);
    }

    /**
     * Get the image being edited.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionEditImage(): Response
    {
        $request = Craft::$app->getRequest();
        $assetId = (int)$request->getRequiredQueryParam('assetId');
        $size = (int)$request->getRequiredQueryParam('size');

        $filePath = Assets::getImageEditorSource($assetId, $size);

        if (!$filePath) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        $response = Craft::$app->getResponse();

        return $response->sendFile($filePath, null, ['inline' => true]);
    }

    /**
     * Save an image according to posted parameters.
     *
     * @return Response
     * @throws BadRequestHttpException if some parameters are missing.
     * @throws \Throwable if something went wrong saving the Asset.
     */
    public function actionSaveImage(): Response
    {
        $this->requireLogin();
        $this->requireAcceptsJson();

        $assets = Craft::$app->getAssets();
        $request = Craft::$app->getRequest();

        try {
            $assetId = $request->getRequiredBodyParam('assetId');
            $viewportRotation = (int)$request->getRequiredBodyParam('viewportRotation');
            $imageRotation = (float)$request->getRequiredBodyParam('imageRotation');
            $replace = $request->getRequiredBodyParam('replace');
            $cropData = $request->getRequiredBodyParam('cropData');
            $focalPoint = $request->getBodyParam('focalPoint');
            $imageDimensions = $request->getBodyParam('imageDimensions');
            $flipData = $request->getBodyParam('flipData');
            $zoom = (float)$request->getBodyParam('zoom', 1);

            $asset = $assets->getAssetById($assetId);

            if (empty($asset)) {
                throw new BadRequestHttpException('The Asset cannot be found');
            }

            $folder = $asset->getFolder();

            if (empty($folder)) {
                throw new BadRequestHttpException('The folder cannot be found');
            }

            // Check the permissions to save in the resolved folder.
            $this->_requirePermissionByAsset('saveAssetInVolume', $asset);

            // If replacing, check for permissions to replace existing Asset files.
            if ($replace) {
                $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume', $asset);
            }

            // Verify parameter adequacy
            if (!in_array($viewportRotation, [0, 90, 180, 270], false)) {
                throw new BadRequestHttpException('Viewport rotation must be 0, 90, 180 or 270 degrees');
            }

            if (
                is_array($cropData) &&
                array_diff(['offsetX', 'offsetY', 'height', 'width'], array_keys($cropData))
            ) {
                throw new BadRequestHttpException('Invalid cropping parameters passed');
            }

            $imageCropped = ($cropData['width'] !== $imageDimensions['width'] || $cropData['height'] !== $imageDimensions['height']);
            $imageRotated = $viewportRotation !== 0 || $imageRotation !== 0.0;
            $imageFlipped = !empty($flipData['x']) || !empty($flipData['y']);
            $imageChanged = $imageCropped || $imageRotated || $imageFlipped;

            $imageCopy = $asset->getCopyOfFile();

            $imageSize = Image::imageSize($imageCopy);

            /** @var Raster $image */
            $image = Craft::$app->getImages()->loadImage($imageCopy, true, max($imageSize));

            // TODO Is this hacky? It seems hacky.
            // We're rasterizing SVG, we have to make sure that the filename change does not get lost
            if (strtolower($asset->getExtension()) === 'svg') {
                unlink($imageCopy);
                $imageCopy = preg_replace('/(svg)$/i', 'png', $imageCopy);
                $asset->filename = preg_replace('/(svg)$/i', 'png', $asset->filename);
            }

            list($originalImageWidth, $originalImageHeight) = $imageSize;

            if ($imageFlipped) {
                if (!empty($flipData['x'])) {
                    $image->flipHorizontally();
                }

                if (!empty($flipData['y'])) {
                    $image->flipVertically();
                }
            }

            if ($zoom !== 1.0) {
                $image->scaleToFit($originalImageWidth * $zoom, $originalImageHeight * $zoom);
            }

            if ($imageRotated) {
                $image->rotate($imageRotation + $viewportRotation);
            }

            $imageCenterX = $image->getWidth() / 2;
            $imageCenterY = $image->getHeight() / 2;

            $adjustmentRatio = min($originalImageWidth / $imageDimensions['width'], $originalImageHeight / $imageDimensions['height']);
            $width = $cropData['width'] * $zoom * $adjustmentRatio;
            $height = $cropData['height'] * $zoom * $adjustmentRatio;
            $x = $imageCenterX + ($cropData['offsetX'] * $zoom * $adjustmentRatio) - $width / 2;
            $y = $imageCenterY + ($cropData['offsetY'] * $zoom * $adjustmentRatio) - $height / 2;

            $focal = null;

            if ($focalPoint) {
                $adjustmentRatio = min($originalImageWidth / $focalPoint['imageDimensions']['width'], $originalImageHeight / $focalPoint['imageDimensions']['height']);
                $fx = $imageCenterX + ($focalPoint['offsetX'] * $zoom * $adjustmentRatio) - $x;
                $fy = $imageCenterY + ($focalPoint['offsetY'] * $zoom * $adjustmentRatio) - $y;

                $focal = [
                    'x' => $fx / $width,
                    'y' => $fy / $height
                ];
            }

            if ($imageCropped) {
                $image->crop($x, $x + $width, $y, $y + $height);
            }

            if ($imageChanged) {
                $image->saveAs($imageCopy);
            }

            if ($replace) {
                $oldFocal = $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null;
                $focalChanged = $focal !== $oldFocal;
                $asset->setFocalPoint($focal);

                if ($focalChanged) {
                    $transforms = Craft::$app->getAssetTransforms();
                    $transforms->deleteCreatedTransformsForAsset($asset);
                    $transforms->deleteTransformIndexDataByAssetId($assetId);
                }

                // Only replace file if it changed, otherwise just save changed focal points
                if ($imageChanged) {
                    $assets->replaceAssetFile($asset, $imageCopy, $asset->filename);
                } else if ($focalChanged) {
                    Craft::$app->getElements()->saveElement($asset);
                }
            } else {
                $newAsset = new Asset();
                $newAsset->avoidFilenameConflicts = true;
                $newAsset->setScenario(Asset::SCENARIO_CREATE);

                $newAsset->tempFilePath = $imageCopy;
                $newAsset->filename = $asset->filename;
                $newAsset->newFolderId = $folder->id;
                $newAsset->volumeId = $folder->volumeId;
                $newAsset->setFocalPoint($focal);

                // Don't validate required custom fields
                Craft::$app->getElements()->saveElement($newAsset);
            }
        } catch (\Throwable $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Download a file.
     *
     * @return Response
     * @throws BadRequestHttpException if the file to download cannot be found.
     */
    public function actionDownloadAsset(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $assetId = Craft::$app->getRequest()->getRequiredBodyParam('assetId');
        $assetService = Craft::$app->getAssets();

        $asset = $assetService->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The Asset you’re trying to download does not exist.'));
        }

        $this->_requirePermissionByAsset('viewVolume', $asset);

        // All systems go, engage hyperdrive! (so PHP doesn't interrupt our stream)
        App::maxPowerCaptain();
        $localPath = $asset->getCopyOfFile();

        $response = Craft::$app->getResponse()
            ->sendFile($localPath, $asset->filename);
        FileHelper::unlink($localPath);

        return $response;
    }

    /**
     * Generates a thumbnail.
     *
     * @param string $uid The asset's UID
     * @param int $width The thumbnail width
     * @param int $height The thumbnail height
     * @return Response
     * @deprecated in 3.0.13. Use [[actionThumb()]] instead.
     */
    public function actionGenerateThumb(string $uid, int $width, int $height): Response
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'The assets/generate-thumb action has been deprecated. Use assets/thumb instead.');
        return $this->actionThumb($uid, $width, $height);
    }

    /**
     * Returns an asset’s thumbnail.
     *
     * @param string $uid The asset's UID
     * @param int $width The thumbnail width
     * @param int $height The thumbnail height
     * @return Response
     * @since 3.0.13
     */
    public function actionThumb(string $uid, int $width, int $height): Response
    {
        $asset = Asset::find()->uid($uid)->one();

        if (!$asset) {
            return $this->_handleImageException(new NotFoundHttpException('Invalid asset UID: ' . $uid));
        }

        try {
            $path = Craft::$app->getAssets()->getThumbPath($asset, $width, $height, true);
        } catch (\Throwable $e) {
            return $this->_handleImageException($e);
        }

        return Craft::$app->getResponse()
            ->setCacheHeaders()
            ->sendFile($path, $asset->getFilename(), [
                'inline' => true,
            ]);
    }

    /**
     * Generate a transform.
     *
     * @param int|null $transformId
     * @return Response
     * @throws NotFoundHttpException if the transform can't be found
     */
    public function actionGenerateTransform(int $transformId = null): Response
    {
        $request = Craft::$app->getRequest();

        // If transform Id was not passed in, see if file id and handle were.
        $assetTransforms = Craft::$app->getAssetTransforms();

        if ($transformId) {
            $transformIndexModel = $assetTransforms->getTransformIndexModelById($transformId);
        } else {
            $assetId = $request->getBodyParam('assetId');
            $handle = $request->getBodyParam('handle');
            $assetModel = Craft::$app->getAssets()->getAssetById($assetId);
            $transformIndexModel = $assetTransforms->getTransformIndex($assetModel, $handle);
        }

        if (!$transformIndexModel) {
            throw new NotFoundHttpException('Image transform not found.');
        }

        $url = $assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['url' => $url]);
        }

        return $this->redirect($url);
    }

    /**
     * Return the file preview for an Asset.
     *
     * @return Response
     * @throws BadRequestHttpException if not a valid request
     */
    public function actionPreviewFile(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $assetId = Craft::$app->getRequest()->getRequiredParam('assetId');
        $requestId = Craft::$app->getRequest()->getRequiredParam('requestId');

        $asset = Asset::find()->id($assetId)->one();

        if (!$asset) {
            return $this->asErrorJson(Craft::t('app', 'Asset not found with that id'));
        }


        if (!$asset->getSupportsPreview()) {
            $modalHtml = '<p class="nopreview centeralign" style="top: calc(50% - 10px) !important; position: relative;">' . Craft::t('app', 'Preview not available.') . '</p>';
        } else {
            if ($asset->kind === 'image') {
                /** @var Volume $volume */
                $volume = $asset->getVolume();

                if ($volume->hasUrls) {
                    $imageUrl = $asset->getUrl();
                } else {
                    $source = $asset->getTransformSource();
                    $imageUrl = Craft::$app->getAssetManager()->getPublishedUrl($source, true);
                }

                $width = $asset->getWidth();
                $height = $asset->getHeight();
                $modalHtml = "<img src=\"$imageUrl\" width=\"{$width}\" height=\"{$height}\" data-maxWidth=\"{$width}\" data-maxHeight=\"{$height}\"/>";
            } else {
                $localCopy = $asset->getCopyOfFile();
                $content = htmlspecialchars(file_get_contents($localCopy));
                $language = $asset->kind === Asset::KIND_HTML ? 'markup' : $asset->kind;
                $modalHtml = '<div class="highlight ' . $asset->kind . '"><pre><code class="language-' . $language . '">' . $content . '</code></pre></div>';
                unlink($localCopy);
            }
        }

        return $this->asJson([
            'success' => true,
            'modalHtml' => $modalHtml,
            'requestId' => $requestId
        ]);
    }

    /**
     * Handles an error when generating a thumb/transform.
     *
     * @param \Exception $e The exception that was thrown
     * @return Response
     */
    private function _handleImageException(\Exception $e): Response
    {
        Craft::$app->getErrorHandler()->logException($e);
        $statusCode = $e instanceof HttpException && $e->statusCode ? $e->statusCode : 500;
        return Craft::$app->getResponse()
            ->sendFile(Craft::getAlias('@app/icons/broken-image.svg'), 'nope.svg', [
                'mimeType' => 'image/svg+xml',
                'inline' => true,
            ])
            ->setStatusCode($statusCode);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param Asset $asset Asset on the Volume on which to require the permission.
     */
    private function _requirePermissionByAsset(string $permissionName, Asset $asset)
    {
        if (!$asset->volumeId) {
            $userTemporaryFolder = Craft::$app->getAssets()->getCurrentUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
            if ($userTemporaryFolder->id == $asset->folderId) {
                return;
            }
        }

        $this->_requirePermissionByVolumeId($permissionName, $asset->volumeId);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param VolumeFolder $folder Folder on the Volume on which to require the permission.
     */
    private function _requirePermissionByFolder(string $permissionName, VolumeFolder $folder)
    {
        if (!$folder->volumeId) {
            $userTemporaryFolder = Craft::$app->getAssets()->getCurrentUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
            if ($userTemporaryFolder->id == $folder->id) {
                return;
            }
        }

        $this->_requirePermissionByVolumeId($permissionName, $folder->volumeId);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param int $volumeId The Volume id on which to require the permission.
     */
    private function _requirePermissionByVolumeId(string $permissionName, int $volumeId)
    {
        $this->requirePermission($permissionName . ':' . $volumeId);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return string
     * @throws UploadFailedException
     */
    private function _getUploadedFileTempPath(UploadedFile $uploadedFile)
    {
        if ($uploadedFile->getHasError()) {
            throw new UploadFailedException($uploadedFile->error);
        }

        // Move the uploaded file to the temp folder
        try {
            $tempPath = $uploadedFile->saveAsTempFile();
        } catch (ErrorException $e) {
            throw new UploadFailedException(0, null, $e);
        }

        if ($tempPath === false) {
            throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
        }

        return $tempPath;
    }
}

