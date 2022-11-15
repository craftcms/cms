<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\assetpreviews\Image as ImagePreview;
use craft\base\Element;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\DeprecationException;
use craft\errors\ElementNotFoundException;
use craft\errors\FsException;
use craft\errors\UploadFailedException;
use craft\errors\VolumeException;
use craft\fields\Assets as AssetsField;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\ImageTransforms;
use craft\helpers\StringHelper;
use craft\i18n\Formatter;
use craft\imagetransforms\ImageTransformer;
use craft\models\VolumeFolder;
use craft\web\Controller;
use craft\web\UploadedFile;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use ZipArchive;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 * Note that all actions in the controller except for [[actionGenerateTransform()]] and [[actionGenerateThumb()]]
 * require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetsController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = ['generate-thumb', 'generate-transform'];

    /**
     * Returns an updated preview image for an asset.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws NotSupportedException
     * @since 3.4.0
     */
    public function actionPreviewThumb(): Response
    {
        $this->requireCpRequest();
        $assetId = $this->request->getRequiredParam('assetId');
        $width = $this->request->getRequiredParam('width');
        $height = $this->request->getRequiredParam('height');

        $asset = Asset::findOne($assetId);
        if ($asset === null) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        return $this->asJson([
            'img' => $asset->getPreviewThumbImg($width, $height),
        ]);
    }

    /**
     * Saves an asset.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @throws Throwable
     * @throws DeprecationException
     * @throws ElementNotFoundException
     * @throws InvalidRouteException
     * @since 3.4.0
     * @deprecated in 4.0.0
     */
    public function actionSaveAsset(): ?Response
    {
        if (UploadedFile::getInstanceByName('assets-upload') !== null) {
            Craft::$app->getDeprecator()->log(__METHOD__, 'Uploading new files via `assets/save-asset` has been deprecated. Use `assets/upload` instead.');
            return $this->runAction('upload');
        }

        $assetId = $this->request->getBodyParam('sourceId') ?? $this->request->getRequiredParam('assetId');
        $siteId = $this->request->getBodyParam('siteId');
        $assetVariable = $this->request->getValidatedBodyParam('assetVariable') ?? 'asset';

        /** @var Asset|null $asset */
        $asset = Asset::find()
            ->id($assetId)
            ->siteId($siteId)
            ->one();

        if ($asset === null) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        $this->requireVolumePermissionByAsset('saveAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('savePeerAssets', $asset);

        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $asset->getSite()->uid);
        }

        $asset->title = $this->request->getParam('title') ?? $asset->title;
        $asset->newFilename = $this->request->getParam('filename');

        $fieldsLocation = $this->request->getParam('fieldsLocation') ?? 'fields';
        $asset->setFieldValuesFromRequest($fieldsLocation);

        // Save the asset
        $asset->setScenario(Element::SCENARIO_LIVE);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            return $this->asModelFailure(
                $asset,
                Craft::t('app', 'Couldn’t save asset.'),
                $assetVariable
            );
        }

        return $this->asModelSuccess(
            $asset,
            Craft::t('app', 'Asset saved.'),
            data: [
                'id' => $asset->id,
                'title' => $asset->title,
                'url' => $asset->getUrl(),
                'cpEditUrl' => $asset->getCpEditUrl(),
            ],
        );
    }

    /**
     * Upload a file
     *
     * @return Response
     * @throws BadRequestHttpException for reasons
     * @since 3.4.0
     */
    public function actionUpload(): Response
    {
        $this->requireAcceptsJson();

        $elementsService = Craft::$app->getElements();
        $uploadedFile = UploadedFile::getInstanceByName('assets-upload');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file was uploaded');
        }

        $folderId = (int)$this->request->getBodyParam('folderId') ?: null;
        $fieldId = (int)$this->request->getBodyParam('fieldId') ?: null;

        if (!$folderId && !$fieldId) {
            throw new BadRequestHttpException('No target destination provided for uploading');
        }

        $assets = Craft::$app->getAssets();

        $tempPath = $this->_getUploadedFileTempPath($uploadedFile);

        if (empty($folderId)) {
            /** @var AssetsField|null $field */
            $field = Craft::$app->getFields()->getFieldById((int)$fieldId);

            if (!$field instanceof AssetsField) {
                throw new BadRequestHttpException('The field provided is not an Assets field');
            }

            if ($elementId = $this->request->getBodyParam('elementId')) {
                $siteId = $this->request->getBodyParam('siteId') ?: null;
                $element = $elementsService->getElementById($elementId, null, $siteId);
            } else {
                $element = null;
            }
            $folderId = $field->resolveDynamicPathToFolderId($element);

            $selectionCondition = $field->getSelectionCondition();
        } else {
            $selectionCondition = null;
        }

        if (empty($folderId)) {
            throw new BadRequestHttpException('The target destination provided for uploading is not valid');
        }

        $folder = $assets->findFolder(['id' => $folderId]);

        if (!$folder) {
            throw new BadRequestHttpException('The target folder provided for uploading is not valid');
        }

        // Check the permissions to upload in the resolved folder.
        $this->requireVolumePermissionByFolder('saveAssets', $folder);

        $filename = Assets::prepareAssetName($uploadedFile->name);

        if ($selectionCondition) {
            $tempFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
            if ($folder->id !== $tempFolder->id) {
                // upload to the user's temp folder initially, with a temp name
                $originalFolder = $folder;
                $originalFilename = $filename;
                $folder = $tempFolder;
                $filename = uniqid('asset', true) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            }
        }

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;

        if (isset($originalFilename)) {
            $asset->title = Assets::filename2Title(pathinfo($originalFilename, PATHINFO_FILENAME));
        }

        $asset->setScenario(Asset::SCENARIO_CREATE);
        $result = $elementsService->saveElement($asset);

        // In case of error, let user know about it.
        if (!$result) {
            $errors = $asset->getFirstErrors();
            return $this->asFailure(implode("\n", $errors));
        }

        if ($selectionCondition) {
            if (!$selectionCondition->matchElement($asset)) {
                // delete and reject it
                $elementsService->deleteElement($asset, true);
                return $this->asFailure(Craft::t('app', '{filename} isn’t selectable for this field.', [
                    'filename' => $uploadedFile->name,
                ]));
            }

            if (isset($originalFilename, $originalFolder)) {
                // move it into the original target destination
                $asset->newFilename = $originalFilename;
                $asset->newFolderId = $originalFolder->id;
                $asset->setScenario(Asset::SCENARIO_MOVE);

                if (!$elementsService->saveElement($asset)) {
                    $errors = $asset->getFirstErrors();
                    return $this->asJson([
                        'error' => $this->asFailure(implode("\n", $errors)),
                    ]);
                }
            }
        }

        if ($asset->conflictingFilename !== null) {
            $conflictingAsset = Asset::findOne(['folderId' => $folder->id, 'filename' => $asset->conflictingFilename]);

            return $this->asJson([
                'conflict' => Craft::t('app', 'A file with the name “{filename}” already exists.', ['filename' => $asset->conflictingFilename]),
                'assetId' => $asset->id,
                'filename' => $asset->conflictingFilename,
                'conflictingAssetId' => $conflictingAsset->id ?? null,
                'suggestedFilename' => $asset->suggestedFilename,
                'conflictingAssetUrl' => ($conflictingAsset && $conflictingAsset->getVolume()->getFs()->hasUrls) ? $conflictingAsset->getUrl() : null,
            ]);
        }

        return $this->asSuccess(data: [
            'filename' => $asset->getFilename(),
            'assetId' => $asset->id,
        ]);
    }

    /**
     * Replace a file
     *
     * @return Response
     * @throws BadRequestHttpException if incorrect combination of parameters passed.
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException if Asset cannot be found by id.
     * @throws VolumeException
     */
    public function actionReplaceFile(): Response
    {
        $this->requireAcceptsJson();
        $assetId = $this->request->getBodyParam('assetId');

        $sourceAssetId = $this->request->getBodyParam('sourceAssetId');
        $targetFilename = $this->request->getBodyParam('targetFilename');
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

        $this->requireVolumePermissionByAsset('replaceFiles', $assetToReplace ?: $sourceAsset);
        $this->requirePeerVolumePermissionByAsset('replacePeerFiles', $assetToReplace ?: $sourceAsset);

        // Handle the Element Action
        if ($assetToReplace !== null && $uploadedFile) {
            $tempPath = $this->_getUploadedFileTempPath($uploadedFile);
            $filename = Assets::prepareAssetName($uploadedFile->name);
            $assets->replaceAssetFile($assetToReplace, $tempPath, $filename);
        } elseif ($sourceAsset !== null) {
            // Or replace using an existing Asset

            // See if we can find an Asset to replace.
            if ($assetToReplace === null) {
                // Make sure the extension didn't change
                if (pathinfo($targetFilename, PATHINFO_EXTENSION) !== $sourceAsset->getExtension()) {
                    throw new Exception($targetFilename . ' doesn\'t have the original file extension.');
                }

                /** @var Asset|null $assetToReplace */
                $assetToReplace = Asset::find()
                    ->select(['elements.id'])
                    ->folderId($sourceAsset->folderId)
                    ->filename(Db::escapeParam($targetFilename))
                    ->one();
            }

            // If we have an actual asset for which to replace the file, just do it.
            if (!empty($assetToReplace)) {
                $tempPath = $sourceAsset->getCopyOfFile();
                $assets->replaceAssetFile($assetToReplace, $tempPath, $assetToReplace->getFilename());
                Craft::$app->getElements()->deleteElement($sourceAsset);
            } else {
                // If all we have is the filename, then make sure that the destination is empty and go for it.
                $volume = $sourceAsset->getVolume();
                $volume->getFs()->deleteFile(rtrim($sourceAsset->folderPath, '/') . '/' . $targetFilename);
                $sourceAsset->newFilename = $targetFilename;
                // Don't validate required custom fields
                Craft::$app->getElements()->saveElement($sourceAsset);
                $assetId = $sourceAsset->id;
            }
        }

        $resultingAsset = $assetToReplace ?: $sourceAsset;

        return $this->asSuccess(data: [
            'assetId' => $assetId,
            'filename' => $resultingAsset->getFilename(),
            'formattedSize' => $resultingAsset->getFormattedSize(0),
            'formattedSizeInBytes' => $resultingAsset->getFormattedSizeInBytes(false),
            'formattedDateUpdated' => Craft::$app->getFormatter()->asDatetime($resultingAsset->dateUpdated, Formatter::FORMAT_WIDTH_SHORT),
            'dimensions' => $resultingAsset->getDimensions(),
        ]);
    }

    /**
     * Create a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the parent folder cannot be found
     * @throws InvalidConfigException
     */
    public function actionCreateFolder(): Response
    {
        $this->requireAcceptsJson();
        $parentId = $this->request->getRequiredBodyParam('parentId');
        $folderName = $this->request->getRequiredBodyParam('folderName');
        $folderName = Assets::prepareAssetName($folderName, false);

        $assets = Craft::$app->getAssets();
        $parentFolder = $assets->findFolder(['id' => $parentId]);

        if (!$parentFolder) {
            throw new BadRequestHttpException('The parent folder cannot be found');
        }

        try {
            // Check if it's possible to create subfolders in target Volume.
            $this->requireVolumePermissionByFolder('createFolders', $parentFolder);

            $folderModel = new VolumeFolder();
            $folderModel->name = $folderName;
            $folderModel->parentId = $parentId;
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path . $folderName . '/';

            $assets->createFolder($folderModel);

            return $this->asSuccess(data: [
                'folderName' => $folderModel->name,
                'folderUid' => $folderModel->uid,
                'folderId' => $folderModel->id,
            ]);
        } catch (UserException $exception) {
            return $this->asFailure($exception->getMessage());
        }
    }

    /**
     * Delete a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws FsException
     * @throws Throwable
     */
    public function actionDeleteFolder(): Response
    {
        $this->requireAcceptsJson();
        $folderId = $this->request->getRequiredBodyParam('folderId');

        $assets = Craft::$app->getAssets();
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->requireVolumePermissionByFolder('deleteAssets', $folder);
        $assets->deleteFoldersByIds($folderId);

        return $this->asSuccess();
    }

    /**
     * Delete an Asset.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the folder cannot be found
     * @throws ForbiddenHttpException
     * @throws Throwable
     */
    public function actionDeleteAsset(): ?Response
    {
        $this->requirePostRequest();

        $assetId = $this->request->getBodyParam('sourceId') ?? $this->request->getRequiredBodyParam('assetId');
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->requireVolumePermissionByAsset('deleteAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerAssets', $asset);

        $success = Craft::$app->getElements()->deleteElement($asset);

        if (!$success) {
            return $this->asModelFailure(
                $asset,
                Craft::t('app', 'Couldn’t delete asset.'),
                'asset'
            );
        }

        return $this->asModelSuccess(
            $asset,
            Craft::t('app', 'Asset deleted.'),
            'asset',
        );
    }

    /**
     * Rename a folder
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException|VolumeException
     */
    public function actionRenameFolder(): Response
    {
        $this->requireAcceptsJson();

        $assets = Craft::$app->getAssets();
        $folderId = $this->request->getRequiredBodyParam('folderId');
        $newName = $this->request->getRequiredBodyParam('newName');
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects and create folders in target Volume.
        $this->requireVolumePermissionByFolder('deleteAssets', $folder);
        $this->requireVolumePermissionByFolder('createFolders', $folder);

        $newName = Craft::$app->getAssets()->renameFolderById($folderId, $newName);
        return $this->asSuccess(data: ['newName' => $newName]);
    }


    /**
     * Move an Asset or multiple Assets.
     *
     * @return Response
     * @throws BadRequestHttpException if the asset or the target folder cannot be found
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public function actionMoveAsset(): Response
    {
        $this->requireAcceptsJson();

        $assetsService = Craft::$app->getAssets();

        // Get the asset
        $assetId = $this->request->getRequiredBodyParam('assetId');
        $asset = $assetsService->getAssetById($assetId);

        if ($asset === null) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        // Get the target folder
        $folderId = $this->request->getBodyParam('folderId', $asset->folderId);
        $folder = $assetsService->getFolderById($folderId);

        if ($folder === null) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Get the target filename
        $filename = $this->request->getBodyParam('filename') ?? $asset->getFilename();

        // Check if it's possible to delete objects in source Volume and save Assets in target Volume.
        $this->requireVolumePermissionByFolder('saveAssets', $folder);
        $this->requireVolumePermissionByAsset('deleteAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('savePeerAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerAssets', $asset);

        if ($this->request->getBodyParam('force')) {
            // Check for a conflicting Asset
            /** @var Asset|null $conflictingAsset */
            $conflictingAsset = Asset::find()
                ->select(['elements.id'])
                ->folderId($folderId)
                ->filename(Db::escapeParam($asset->getFilename()))
                ->one();

            // If there's an Asset conflicting, then merge and replace file.
            if ($conflictingAsset) {
                Craft::$app->getElements()->mergeElementsByIds($conflictingAsset->id, $asset->id);
            } else {
                $volume = $folder->getVolume();
                $volume->getFs()->deleteFile(rtrim($folder->path, '/') . '/' . $asset->getFilename());
            }
        }

        $result = $assetsService->moveAsset($asset, $folder, $filename);

        if (!$result) {
            // Get the corrected filename
            [, $filename] = Assets::parseFileLocation($asset->newLocation);

            return $this->asJson([
                'conflict' => $asset->getFirstError('newLocation'),
                'suggestedFilename' => $asset->suggestedFilename,
                'filename' => $filename,
                'assetId' => $asset->id,
            ]);
        }

        return $this->asSuccess(data: [
            'success' => true,
        ]);
    }

    /**
     * Move a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder to move, or the destination parent folder, cannot be found
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @throws Throwable
     */
    public function actionMoveFolder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $folderBeingMovedId = $this->request->getRequiredBodyParam('folderId');
        $newParentFolderId = $this->request->getRequiredBodyParam('parentId');
        $force = $this->request->getBodyParam('force', false);
        $merge = !$force ? $this->request->getBodyParam('merge', false) : false;

        $assets = Craft::$app->getAssets();
        $folderToMove = $assets->getFolderById($folderBeingMovedId);
        $destinationFolder = $assets->getFolderById($newParentFolderId);

        if ($folderToMove === null) {
            throw new BadRequestHttpException('The folder you are trying to move does not exist');
        }

        if ($destinationFolder === null) {
            throw new BadRequestHttpException('The destination folder does not exist');
        }

        // Check if it's possible to delete objects in source Volume, create folders
        // in target Volume and save Assets in target Volume.
        $this->requireVolumePermissionByFolder('deleteAssets', $folderToMove);
        $this->requireVolumePermissionByFolder('createFolders', $destinationFolder);
        $this->requireVolumePermissionByFolder('saveAssets', $destinationFolder);

        $targetVolume = $destinationFolder->getVolume();

        $existingFolder = $assets->findFolder([
            'parentId' => $newParentFolderId,
            'name' => $folderToMove->name,
        ]);

        if (!$existingFolder) {
            $existingFolder = $targetVolume->getFs()->directoryExists(rtrim($destinationFolder->path, '/') . '/' . $folderToMove->name);
        }

        // If this a conflict and no force or merge flags were passed in then STOP RIGHT THERE!
        if ($existingFolder && !$force && !$merge) {
            // Throw a prompt
            return $this->asJson([
                'conflict' => Craft::t('app', 'Folder “{folder}” already exists at target location', ['folder' => $folderToMove->name]),
                'folderId' => $folderBeingMovedId,
                'parentId' => $newParentFolderId,
            ]);
        }

        $sourceTree = $assets->getAllDescendantFolders($folderToMove);

        if (!$existingFolder) {
            // No conflicts, mirror the existing structure
            $folderIdChanges = Assets::mirrorFolderStructure($folderToMove, $destinationFolder);

            // Get the file transfer list.
            $allSourceFolderIds = array_keys($sourceTree);
            $allSourceFolderIds[] = $folderBeingMovedId;
            /** @var Asset[] $foundAssets */
            $foundAssets = Asset::find()
                ->folderId($allSourceFolderIds)
                ->all();
            $fileTransferList = Assets::fileTransferList($foundAssets, $folderIdChanges);
        } else {
            $targetTreeMap = [];

            // If an indexed folder is conflicting
            if ($existingFolder instanceof VolumeFolder) {
                // Delete if using force
                if ($force) {
                    try {
                        $assets->deleteFoldersByIds($existingFolder->id);
                    } catch (VolumeException $exception) {
                        Craft::$app->getErrorHandler()->logException($exception);
                        return $this->asFailure(Craft::t('app', 'Directories cannot be deleted while moving assets.'));
                    }
                } else {
                    // Or build a map of existing folders for file move
                    $targetTree = $assets->getAllDescendantFolders($existingFolder);
                    $targetPrefixLength = strlen($destinationFolder->path);

                    foreach ($targetTree as $existingFolder) {
                        $targetTreeMap[substr($existingFolder->path,
                            $targetPrefixLength)] = $existingFolder->id;
                    }
                }
            } elseif ($force) {
                // An un-indexed folder is conflicting. If we're forcing things, just remove it.
                $targetVolume->getFs()->deleteDirectory(rtrim($destinationFolder->path, '/') . '/' . $folderToMove->name);
            }

            // Mirror the structure, passing along the exsting folder map
            $folderIdChanges = Assets::mirrorFolderStructure($folderToMove, $destinationFolder, $targetTreeMap);

            // Get file transfer list for the progress bar
            $allSourceFolderIds = array_keys($sourceTree);
            $allSourceFolderIds[] = $folderBeingMovedId;
            /** @var Asset[] $foundAssets */
            $foundAssets = Asset::find()
                ->folderId($allSourceFolderIds)
                ->all();
            $fileTransferList = Assets::fileTransferList($foundAssets, $folderIdChanges);
        }

        $newFolderId = $folderIdChanges[$folderBeingMovedId] ?? null;
        $newFolder = $assets->getFolderById($newFolderId);

        return $this->asSuccess(data: [
            'success' => true,
            'transferList' => $fileTransferList,
            'newFolderUid' => $newFolder->uid,
            'newFolderId' => $newFolderId,
        ]);
    }

    /**
     * Return the image editor template.
     *
     * @return Response
     * @throws BadRequestHttpException if the Asset is missing.
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function actionImageEditor(): Response
    {
        $assetId = $this->request->getRequiredBodyParam('assetId');
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The asset you’re trying to edit does not exist.'));
        }

        $focal = $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null;

        $html = $this->getView()->renderTemplate('_special/image_editor.twig');

        return $this->asJson(['html' => $html, 'focalPoint' => $focal]);
    }

    /**
     * Get the image being edited.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionEditImage(): Response
    {
        $assetId = (int)$this->request->getRequiredQueryParam('assetId');
        $size = (int)$this->request->getRequiredQueryParam('size');

        $asset = Asset::findOne($assetId);
        if (!$asset) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        try {
            $url = Craft::$app->getAssets()->getImagePreviewUrl($asset, $size, $size);
            return $this->response->redirect($url);
        } catch (NotSupportedException) {
            // just output the file contents
            $path = ImageTransforms::getLocalImageSource($asset);
            return $this->response->sendFile($path, $asset->getFilename());
        }
    }

    /**
     * Save an image according to posted parameters.
     *
     * @return Response
     * @throws BadRequestHttpException if some parameters are missing.
     * @throws Throwable if something went wrong saving the Asset.
     */
    public function actionSaveImage(): Response
    {
        $this->requireAcceptsJson();
        $assets = Craft::$app->getAssets();

        $assetId = $this->request->getRequiredBodyParam('assetId');
        $viewportRotation = (int)$this->request->getRequiredBodyParam('viewportRotation');
        $imageRotation = (float)$this->request->getRequiredBodyParam('imageRotation');
        $replace = $this->request->getRequiredBodyParam('replace');
        $cropData = $this->request->getRequiredBodyParam('cropData');
        $focalPoint = $this->request->getBodyParam('focalPoint');
        $imageDimensions = $this->request->getBodyParam('imageDimensions');
        $flipData = $this->request->getBodyParam('flipData');
        $zoom = (float)$this->request->getBodyParam('zoom', 1);

        $asset = $assets->getAssetById($assetId);

        if ($asset === null) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        $folder = $asset->getFolder();

        // Do what you want with your own photo.
        if ($asset->id != static::currentUser()->photoId) {
            $this->requireVolumePermissionByAsset('editImages', $asset);
            $this->requirePeerVolumePermissionByAsset('editPeerImages', $asset);
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

        // TODO Fire an event for any other image editing takers.
        $transformer = new ImageTransformer();

        $originalImageWidth = $asset->width;
        $originalImageHeight = $asset->height;

        $transformer->startImageEditing($asset);

        $imageCropped = ($cropData['width'] !== $imageDimensions['width'] || $cropData['height'] !== $imageDimensions['height']);
        $imageRotated = $viewportRotation !== 0 || $imageRotation !== 0.0;
        $imageFlipped = !empty($flipData['x']) || !empty($flipData['y']);
        $imageChanged = $imageCropped || $imageRotated || $imageFlipped;

        if ($imageFlipped) {
            $transformer->flipImage(!empty($flipData['x']), !empty($flipData['y']));
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $upscale = $generalConfig->upscaleImages;
        $generalConfig->upscaleImages = true;

        if ($zoom !== 1.0) {
            $transformer->scaleImage((int)($originalImageWidth * $zoom), (int)($originalImageHeight * $zoom));
        }

        $generalConfig->upscaleImages = $upscale;

        if ($imageRotated) {
            $transformer->rotateImage($imageRotation + $viewportRotation);
        }

        $imageCenterX = $transformer->getEditedImageWidth() / 2;
        $imageCenterY = $transformer->getEditedImageHeight() / 2;

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
                'y' => $fy / $height,
            ];
        }

        if ($imageCropped) {
            $transformer->crop((int)$x, (int)$y, (int)$width, (int)$height);
        }

        if ($imageChanged) {
            $finalImage = $transformer->finishImageEditing();
        } else {
            $finalImage = $transformer->cancelImageEditing();
        }

        $output = [];

        if ($replace) {
            $oldFocal = $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null;
            $focalChanged = $focal !== $oldFocal;
            $asset->setFocalPoint($focal);

            if ($focalChanged) {
                $transforms = Craft::$app->getImageTransforms();
                $transforms->deleteCreatedTransformsForAsset($asset);
            }

            // Only replace file if it changed, otherwise just save changed focal points
            if ($imageChanged) {
                $assets->replaceAssetFile($asset, $finalImage, $asset->getFilename());
            } elseif ($focalChanged) {
                Craft::$app->getElements()->saveElement($asset);
            }
        } else {
            $newAsset = new Asset();
            $newAsset->avoidFilenameConflicts = true;
            $newAsset->setScenario(Asset::SCENARIO_CREATE);

            $newAsset->tempFilePath = $finalImage;
            $newAsset->setFilename($asset->getFilename());
            $newAsset->newFolderId = $folder->id;
            $newAsset->setVolumeId($folder->volumeId);
            $newAsset->setFocalPoint($focal);

            // Don't validate required custom fields
            Craft::$app->getElements()->saveElement($newAsset);

            $output['newAssetId'] = $newAsset->id;
        }

        return $this->asSuccess(data: $output);
    }

    /**
     * Download a file.
     *
     * @return Response
     * @throws AssetException
     * @throws BadRequestHttpException if the file to download cannot be found.
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @throws RangeNotSatisfiableHttpException
     */
    public function actionDownloadAsset(): Response
    {
        $this->requirePostRequest();

        $assetIds = $this->request->getRequiredBodyParam('assetId');
        /** @var Asset[] $assets */
        $assets = Asset::find()
            ->id($assetIds)
            ->all();

        if (empty($assets)) {
            throw new BadRequestHttpException(Craft::t('app', 'The asset you’re trying to download does not exist.'));
        }

        foreach ($assets as $asset) {
            $this->requireVolumePermissionByAsset('viewAssets', $asset);
            $this->requirePeerVolumePermissionByAsset('viewPeerAssets', $asset);
        }

        // If only one asset was selected, send it back unzipped
        if (count($assets) === 1) {
            $asset = reset($assets);
            return $this->response
                ->sendStreamAsFile($asset->getStream(), $asset->getFilename(), [
                    'fileSize' => $asset->size,
                    'mimeType' => $asset->getMimeType(),
                ]);
        }

        // Otherwise create a zip of all the selected assets
        $zipPath = Craft::$app->getPath()->getTempPath() . '/' . StringHelper::UUID() . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip at ' . $zipPath);
        }

        App::maxPowerCaptain();

        foreach ($assets as $asset) {
            $path = $asset->getVolume()->name . '/' . $asset->getPath();
            $zip->addFromString($path, $asset->getContents());
        }

        $zip->close();

        return $this->response
            ->sendFile($zipPath, 'assets.zip');
    }

    /**
     * Returns a file icon with an extension.
     *
     * @param string $extension The asset’s UID
     * @return Response
     * @since 4.0.0
     */
    public function actionIcon(string $extension): Response
    {
        $path = Assets::iconPath($extension);

        return $this->response
            ->setCacheHeaders()
            ->sendFile($path, "$extension.svg", [
                'inline' => true,
            ]);
    }

    /**
     * Generate a transform.
     *
     * @param int|null $transformId
     * @return Response
     * @throws NotFoundHttpException if the transform can't be found
     * @throws ServerErrorHttpException if the transform can't be generated
     */
    public function actionGenerateTransform(?int $transformId = null): Response
    {
        try {
            // If transform Id was not passed in, see if file id and handle were.
            if ($transformId) {
                $transformer = Craft::createObject(ImageTransformer::class);
                $transformIndexModel = $transformer->getTransformIndexModelById($transformId);
                $assetId = $transformIndexModel->assetId;
                $transform = $transformIndexModel->getTransform();
            } else {
                $assetId = $this->request->getRequiredBodyParam('assetId');
                $handle = $this->request->getRequiredBodyParam('handle');
                $transform = ImageTransforms::normalizeTransform($handle);
                $transformer = $transform->getImageTransformer();
            }
        } catch (\Exception $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            throw new ServerErrorHttpException('Image transform cannot be created.', 0, $exception);
        }

        $asset = Asset::findOne(['id' => $assetId]);

        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $url = $transformer->getTransformUrl($asset, $transform, true);

        if ($this->request->getAcceptsJson()) {
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
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $assetId = $this->request->getRequiredParam('assetId');
        $requestId = $this->request->getRequiredParam('requestId');

        /** @var Asset|null $asset */
        $asset = Asset::find()->id($assetId)->one();

        if (!$asset) {
            return $this->asFailure(Craft::t('app', 'Asset not found with that id'));
        }

        $previewHtml = null;

        $previewHandler = Craft::$app->getAssets()->getAssetPreviewHandler($asset);
        $variables = [];

        if ($previewHandler instanceof ImagePreview) {
            if ($asset->id != static::currentUser()->photoId) {
                $variables['editFocal'] = true;

                try {
                    $this->requireVolumePermissionByAsset('editImages', $asset);
                    $this->requirePeerVolumePermissionByAsset('editPeerImages', $asset);
                } catch (ForbiddenHttpException) {
                    $variables['editFocal'] = false;
                }
            }
        }

        if ($previewHandler) {
            try {
                $previewHtml = $previewHandler->getPreviewHtml($variables);
            } catch (NotSupportedException) {
                // No big deal
            }
        }

        $view = $this->getView();

        return $this->asSuccess(data: [
            'previewHtml' => $previewHtml,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'requestId' => $requestId,
        ]);
    }

    /**
     * Update an asset's focal point position.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     */
    public function actionUpdateFocalPosition(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $assetUid = Craft::$app->getRequest()->getRequiredBodyParam('assetUid');
        $focalData = Craft::$app->getRequest()->getRequiredBodyParam('focal');
        $focalEnabled = Craft::$app->getRequest()->getRequiredBodyParam('focalEnabled');

        // if focal point is disabled, set focal data to null (can't pass null to $focalData as it's a required param)
        if ($focalEnabled === false) {
            $focalData = null;
        }

        /** @var Asset|null $asset */
        $asset = Asset::find()->uid($assetUid)->one();

        if (!$asset) {
            throw new BadRequestHttpException("Invalid asset UID: $assetUid");
        }

        $this->requireVolumePermissionByAsset('editImages', $asset);
        $this->requirePeerVolumePermissionByAsset('editPeerImages', $asset);

        $asset->setFocalPoint($focalData);
        Craft::$app->getElements()->saveElement($asset);
        Craft::$app->getImageTransforms()->deleteCreatedTransformsForAsset($asset);

        return $this->asSuccess();
    }

    /**
     * Sends a broken image response based on a given exception.
     *
     * @param Throwable|null $e The exception that was thrown
     * @return Response
     * @since 3.4.8
     */
    protected function asBrokenImage(?Throwable $e = null): Response
    {
        $statusCode = $e instanceof HttpException && $e->statusCode ? $e->statusCode : 500;
        return $this->response
            ->sendFile(Craft::getAlias('@appicons/broken-image.svg'), 'nope.svg', [
                'mimeType' => 'image/svg+xml',
                'inline' => true,
            ])
            ->setStatusCode($statusCode);
    }

    /**
     * Requires a volume permission by a given asset.
     *
     * @param string $permissionName The name of the permission to require (sans `:<volume-uid>` suffix)
     * @param Asset $asset The asset whose volume should be checked
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @since 3.4.8
     */
    protected function requireVolumePermissionByAsset(string $permissionName, Asset $asset): void
    {
        if (!$asset->getVolumeId()) {
            $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            // Skip permission check only if it’s the user’s temporary folder
            if ($userTemporaryFolder->id == $asset->folderId) {
                return;
            }
        }

        $volume = $asset->getVolume();
        $this->requireVolumePermission($permissionName, $volume->uid);
    }

    /**
     * Requires a volume permission by a given asset, only if it wasn't uploaded by the current user.
     *
     * @param string $permissionName The name of the peer permission to require (sans `:<volume-uid>` suffix)
     * @param Asset $asset The asset whose volume should be checked
     * @throws ForbiddenHttpException
     * @since 3.4.8
     */
    protected function requirePeerVolumePermissionByAsset(string $permissionName, Asset $asset): void
    {
        if ($asset->getVolumeId()) {
            $userId = Craft::$app->getUser()->getId();
            if ($asset->uploaderId != $userId) {
                $this->requireVolumePermissionByAsset($permissionName, $asset);
            }
        }
    }

    /**
     * Requires a volume permission by a given folder.
     *
     * @param string $permissionName The name of the peer permission to require (sans `:<volume-uid>` suffix)
     * @param VolumeFolder $folder The folder whose volume should be checked
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws VolumeException
     * @since 3.4.8
     */
    protected function requireVolumePermissionByFolder(string $permissionName, VolumeFolder $folder): void
    {
        if (!$folder->volumeId) {
            $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            // Skip permission check only if it’s the user’s temporary folder
            if ($userTemporaryFolder->id == $folder->id) {
                return;
            }
        }

        $volume = $folder->getVolume();
        $this->requireVolumePermission($permissionName, $volume->uid);
    }

    /**
     * Requires a volume permission by its UID.
     *
     * @param string $permissionName The name of the peer permission to require (sans `:<volume-uid>` suffix)
     * @param string $volumeUid The volume’s UID
     * @throws ForbiddenHttpException
     * @since 3.4.8
     */
    protected function requireVolumePermission(string $permissionName, string $volumeUid): void
    {
        $this->requirePermission($permissionName . ':' . $volumeUid);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return string
     * @throws UploadFailedException
     */
    private function _getUploadedFileTempPath(UploadedFile $uploadedFile): string
    {
        if ($uploadedFile->getHasError()) {
            throw new UploadFailedException($uploadedFile->error);
        }

        // Move the uploaded file to the temp folder
        $tempPath = $uploadedFile->saveAsTempFile();

        if ($tempPath === false) {
            throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
        }

        return $tempPath;
    }
}
