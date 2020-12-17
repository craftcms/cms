<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\AssetLogicException;
use craft\errors\UploadFailedException;
use craft\fields\Assets as AssetsField;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\image\Raster;
use craft\models\VolumeFolder;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
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
    protected $allowAnonymous = ['generate-thumb', 'generate-transform'];

    /**
     * Edits an asset.
     *
     * @param int $assetId The asset ID
     * @param Asset|null $asset The asset being edited, if there were any validation errors.
     * @param string|null $site The site handle, if specified.
     * @return Response
     * @throws BadRequestHttpException if `$assetId` is invalid
     * @throws ForbiddenHttpException if the user isn't permitted to edit the asset
     * @since 3.4.0
     */
    public function actionEditAsset(int $assetId, Asset $asset = null, string $site = null): Response
    {
        $sitesService = Craft::$app->getSites();
        $editableSiteIds = $sitesService->getEditableSiteIds();
        if ($site !== null) {
            $siteHandle = $site;
            $site = $sitesService->getSiteByHandle($siteHandle);
            if (!$site) {
                throw new BadRequestHttpException("Invalid site handle: {$siteHandle}");
            }
            if (!in_array($site->id, $editableSiteIds, false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        } else {
            $site = $sitesService->getCurrentSite();
            if (!in_array($site->id, $editableSiteIds, false)) {
                $site = $sitesService->getSiteById($editableSiteIds[0]);
            }
        }

        if ($asset === null) {
            $asset = Asset::find()
                ->id($assetId)
                ->siteId($site->id)
                ->one();
            if ($asset === null) {
                throw new BadRequestHttpException("Invalid asset ID: {$assetId}");
            }
        }

        $this->requireVolumePermissionByAsset('viewVolume', $asset);
        $this->requirePeerVolumePermissionByAsset('viewPeerFilesInVolume', $asset);

        $volume = $asset->getVolume();

        $crumbs = [
            [
                'label' => Craft::t('app', 'Assets'),
                'url' => UrlHelper::url('assets')
            ],
            [
                'label' => Craft::t('site', $volume->name),
                'url' => UrlHelper::url("assets/{$volume->handle}")
            ],
        ];

        // See if we can show a thumbnail
        try {
            // Is the image editable, and is the user allowed to edit?
            $userSession = Craft::$app->getUser();

            $editable = (
                $asset->getSupportsImageEditor() &&
                $userSession->checkPermission("editImagesInVolume:{$volume->uid}") &&
                ($userSession->getId() == $asset->uploaderId || $userSession->checkPermission("editPeerImagesInVolume:{$volume->uid}"))
            );

            $previewHtml = '<div id="preview-thumb-container" class="preview-thumb-container">' .
                '<div class="preview-thumb">' .
                $asset->getPreviewThumbImg(350, 190) .
                '</div>' .
                '<div class="buttons">';

            if (Craft::$app->getAssets()->getAssetPreviewHandler($asset) !== null) {
                $previewHtml .= '<div class="btn" id="preview-btn">' . Craft::t('app', 'Preview') . '</div>';
            }

            if ($editable) {
                $previewHtml .= '<div class="btn" id="edit-btn">' . Craft::t('app', 'Edit') . '</div>';
            }

            $previewHtml .= '</div></div>';
        } catch (NotSupportedException $e) {
            // NBD
            $previewHtml = '';
        }

        // See if the user is allowed to replace the file
        $userSession = Craft::$app->getUser();
        $canReplaceFile = (
            $userSession->checkPermission("deleteFilesAndFoldersInVolume:{$volume->uid}") &&
            ($userSession->getId() == $asset->uploaderId || $userSession->checkPermission("replacePeerFilesInVolume:{$volume->uid}"))
        );

        // See if the user is allowed to delete the asset
        try {
            $this->requireVolumePermissionByAsset('deleteFilesAndFoldersInVolume', $asset);
            $this->requirePeerVolumePermissionByAsset('deletePeerFilesInVolume', $asset);
            $canDelete = true;
        } catch (ForbiddenHttpException $e) {
            $canDelete = false;
        }

        if (in_array($asset->kind, [Asset::KIND_IMAGE, Asset::KIND_PDF, Asset::KIND_TEXT])) {
            $assetUrl = $asset->getUrl();
        } else {
            $assetUrl = null;
        }

        return $this->renderTemplate('assets/_edit', [
            'element' => $asset,
            'volume' => $volume,
            'assetUrl' => $assetUrl,
            'title' => trim($asset->title) ?: Craft::t('app', 'Edit Asset'),
            'crumbs' => $crumbs,
            'previewHtml' => $previewHtml,
            'formattedSize' => $asset->getFormattedSize(0),
            'formattedSizeInBytes' => $asset->getFormattedSizeInBytes(false),
            'dimensions' => $asset->getDimensions(),
            'canReplaceFile' => $canReplaceFile,
            'canEdit' => $asset->getIsEditable(),
            'canDeleteSource' => $canDelete,
        ]);
    }

    /**
     * Returns an updated preview image for an asset.
     *
     * @return Response
     * @throws BadRequestHttpException
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
            throw new BadRequestHttpException("Invalid asset ID: {$assetId}");
        }

        return $this->asJson([
            'img' => $asset->getPreviewThumbImg($width, $height),
        ]);
    }

    /**
     * Saves an asset.
     *
     * @return Response|null
     * @since 3.4.0
     */
    public function actionSaveAsset()
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
            throw new BadRequestHttpException("Invalid asset ID: {$assetId}");
        }

        $this->requireVolumePermissionByAsset('saveAssetInVolume', $asset);
        $this->requirePeerVolumePermissionByAsset('editPeerFilesInVolume', $asset);

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
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $asset->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t save asset.'));

            // Send the asset back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                $assetVariable => $asset
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $asset->id,
                'title' => $asset->title,
                'url' => $asset->getUrl(),
                'cpEditUrl' => $asset->getCpEditUrl()
            ]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Asset saved.'));
        return $this->redirectToPostedUrl($asset);
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
        $uploadedFile = UploadedFile::getInstanceByName('assets-upload');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file was uploaded');
        }

        $folderId = $this->request->getBodyParam('folderId');
        $fieldId = $this->request->getBodyParam('fieldId');

        if (!$folderId && !$fieldId) {
            throw new BadRequestHttpException('No target destination provided for uploading');
        }

        try {
            $assets = Craft::$app->getAssets();

            $tempPath = $this->_getUploadedFileTempPath($uploadedFile);

            if (empty($folderId)) {
                $field = Craft::$app->getFields()->getFieldById((int)$fieldId);

                if (!($field instanceof AssetsField)) {
                    throw new BadRequestHttpException('The field provided is not an Assets field');
                }

                if ($elementId = $this->request->getBodyParam('elementId')) {
                    $siteId = $this->request->getBodyParam('siteId') ?: null;
                    $element = Craft::$app->getElements()->getElementById($elementId, null, $siteId);
                } else {
                    $element = null;
                }
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
            $this->requireVolumePermissionByFolder('saveAssetInVolume', $folder);

            $filename = Assets::prepareAssetName($uploadedFile->name);

            $asset = new Asset();
            $asset->tempFilePath = $tempPath;
            $asset->filename = $filename;
            $asset->newFolderId = $folder->id;
            $asset->setVolumeId($folder->volumeId);
            $asset->uploaderId = Craft::$app->getUser()->getId();
            $asset->avoidFilenameConflicts = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);

            $result = Craft::$app->getElements()->saveElement($asset);

            // In case of error, let user know about it.
            if (!$result) {
                $errors = $asset->getFirstErrors();
                return $this->asErrorJson(Craft::t('app', 'Failed to save the asset:') . ' ' . implode(";\n", $errors));
            }

            if ($asset->conflictingFilename !== null) {
                $conflictingAsset = Asset::findOne(['folderId' => $folder->id, 'filename' => $asset->conflictingFilename]);

                return $this->asJson([
                    'conflict' => Craft::t('app', 'A file with the name “{filename}” already exists.', ['filename' => $asset->conflictingFilename]),
                    'assetId' => $asset->id,
                    'filename' => $asset->conflictingFilename,
                    'conflictingAssetId' => $conflictingAsset ? $conflictingAsset->id : null,
                    'suggestedFilename' => $asset->suggestedFilename,
                    'conflictingAssetUrl' => ($conflictingAsset && $conflictingAsset->getVolume()->hasUrls) ? $conflictingAsset->getUrl() : null
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

        $this->requireVolumePermissionByAsset('replaceFilesInVolume', $assetToReplace ?: $sourceAsset);
        $this->requirePeerVolumePermissionByAsset('replacePeerFilesInVolume', $assetToReplace ?: $sourceAsset);

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

        $resultingAsset = $assetToReplace ?: $sourceAsset;

        return $this->asJson([
            'success' => true,
            'assetId' => $assetId,
            'filename' => $resultingAsset->filename,
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
            $this->requireVolumePermissionByFolder('createFoldersInVolume', $parentFolder);

            $folderModel = new VolumeFolder();
            $folderModel->name = $folderName;
            $folderModel->parentId = $parentId;
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path . $folderName . '/';

            $assets->createFolder($folderModel);

            return $this->asJson([
                'success' => true,
                'folderName' => $folderModel->name,
                'folderUid' => $folderModel->uid,
                'folderId' => $folderModel->id
            ]);
        } catch (AssetException $exception) {
            return $this->asErrorJson($exception->getMessage());
        } catch (ForbiddenHttpException $exception) {
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
        $this->requireAcceptsJson();
        $folderId = $this->request->getRequiredBodyParam('folderId');

        $assets = Craft::$app->getAssets();
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->requireVolumePermissionByFolder('deleteFilesAndFoldersInVolume', $folder);
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
     * @return Response|null
     * @throws BadRequestHttpException if the folder cannot be found
     * @throws ForbiddenHttpException
     * @throws AssetException
     */
    public function actionDeleteAsset()
    {
        $this->requirePostRequest();

        $assetId = $this->request->getBodyParam('sourceId') ?? $this->request->getRequiredBodyParam('assetId');
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->requireVolumePermissionByAsset('deleteFilesAndFoldersInVolume', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerFilesInVolume', $asset);

        try {
            $success = Craft::$app->getElements()->deleteElement($asset);
        } catch (AssetException $e) {
            if ($this->request->getAcceptsJson()) {
                return $this->asErrorJson($e->getMessage());
            }
            throw $e;
        }

        if (!$success) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete asset.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'asset' => $asset
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Asset deleted.'));
        return $this->redirectToPostedUrl($asset);
    }

    /**
     * Rename a folder
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
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
        $this->requireVolumePermissionByFolder('deleteFilesAndFoldersInVolume', $folder);
        $this->requireVolumePermissionByFolder('createFoldersInVolume', $folder);

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
        $this->requireAcceptsJson();

        $assetsService = Craft::$app->getAssets();

        // Get the asset
        $assetId = $this->request->getRequiredBodyParam('assetId');
        $asset = $assetsService->getAssetById($assetId);

        if (empty($asset)) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        // Get the target folder
        $folderId = $this->request->getBodyParam('folderId', $asset->folderId);
        $folder = $assetsService->getFolderById($folderId);

        if (empty($folder)) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Get the target filename
        $filename = $this->request->getBodyParam('filename', $asset->filename);

        // Check if it's possible to delete objects in source Volume and save Assets in target Volume.
        $this->requireVolumePermissionByFolder('saveAssetInVolume', $folder);
        $this->requireVolumePermissionByAsset('deleteFilesAndFoldersInVolume', $asset);
        $this->requirePeerVolumePermissionByAsset('editPeerFilesInVolume', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerFilesInVolume', $asset);

        if ($this->request->getBodyParam('force')) {
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
        $folderBeingMovedId = $this->request->getRequiredBodyParam('folderId');
        $newParentFolderId = $this->request->getRequiredBodyParam('parentId');
        $force = $this->request->getBodyParam('force', false);
        $merge = !$force ? $this->request->getBodyParam('merge', false) : false;

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
        $this->requireVolumePermissionByFolder('deleteFilesAndFoldersInVolume', $folderToMove);
        $this->requireVolumePermissionByFolder('createFoldersInVolume', $destinationFolder);
        $this->requireVolumePermissionByFolder('saveAssetInVolume', $destinationFolder);

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

        $newFolderId = $folderIdChanges[$folderBeingMovedId] ?? null;
        $newFolder = $assets->getFolderById($newFolderId);

        return $this->asJson([
            'success' => true,
            'transferList' => $fileTransferList,
            'newFolderUid' => $newFolder->uid,
            'newFolderId' => $newFolderId
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
        $assetId = $this->request->getRequiredBodyParam('assetId');
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The asset you’re trying to edit does not exist.'));
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
        $assetId = (int)$this->request->getRequiredQueryParam('assetId');
        $size = (int)$this->request->getRequiredQueryParam('size');

        $filePath = Assets::getImageEditorSource($assetId, $size);

        if (!$filePath) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        return $this->response->sendFile($filePath, null, ['inline' => true]);
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
        $this->requireAcceptsJson();

        $assets = Craft::$app->getAssets();
        try {
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

            if (empty($asset)) {
                throw new BadRequestHttpException('The Asset cannot be found');
            }

            $folder = $asset->getFolder();

            if (empty($folder)) {
                throw new BadRequestHttpException('The folder cannot be found');
            }

            // Do what you want with your own photo.
            if ($asset->id != Craft::$app->getUser()->getIdentity()->photoId) {
                $this->requireVolumePermissionByAsset('editImagesInVolume', $asset);
                $this->requirePeerVolumePermissionByAsset('editPeerImagesInVolume', $asset);
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

            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $upscale = $generalConfig->upscaleImages;
            $generalConfig->upscaleImages = true;

            if ($zoom !== 1.0) {
                $image->scaleToFit($originalImageWidth * $zoom, $originalImageHeight * $zoom);
            }

            $generalConfig->upscaleImages = $upscale;

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
                $newAsset->setVolumeId($folder->volumeId);
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
        $this->requirePostRequest();

        $assetIds = $this->request->getRequiredBodyParam('assetId');
        $assets = Asset::find()
            ->id($assetIds)
            ->all();

        if (empty($assets)) {
            throw new BadRequestHttpException(Craft::t('app', 'The asset you’re trying to download does not exist.'));
        }

        foreach ($assets as $asset) {
            $this->requireVolumePermissionByAsset('viewVolume', $asset);
            $this->requirePeerVolumePermissionByAsset('viewPeerFilesInVolume', $asset);
        }

        // If only one asset was selected, send it back unzipped
        if (count($assets) === 1) {
            $asset = reset($assets);
            return $this->response
                ->sendStreamAsFile($asset->getStream(), $asset->filename, [
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
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `assets/generate-thumb` action has been deprecated. Use `assets/thumb` instead.');
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
            $e = new NotFoundHttpException('Invalid asset UID: ' . $uid);
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asBrokenImage($e);
        }

        try {
            $path = Craft::$app->getAssets()->getThumbPath($asset, $width, $height, true);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asBrokenImage($e);
        }

        return $this->response
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
     * @throws ServerErrorHttpException if the transform can't be generated
     */
    public function actionGenerateTransform(int $transformId = null): Response
    {
        // If transform Id was not passed in, see if file id and handle were.
        $assetTransforms = Craft::$app->getAssetTransforms();

        if ($transformId) {
            $transformIndexModel = $assetTransforms->getTransformIndexModelById($transformId);
        } else {
            $assetId = $this->request->getRequiredBodyParam('assetId');
            $handle = $this->request->getRequiredBodyParam('handle');
            $assetModel = Craft::$app->getAssets()->getAssetById($assetId);
            if ($assetModel === null) {
                throw new BadRequestHttpException('Invalid asset ID: ' . $assetId);
            }
            $transformIndexModel = $assetTransforms->getTransformIndex($assetModel, $handle);
        }

        if (!$transformIndexModel) {
            throw new NotFoundHttpException('Image transform not found.');
        }

        try {
            $url = $assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);
        } catch (\Exception $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            throw new ServerErrorHttpException('Image transform cannot be created.');
        }

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

        $asset = Asset::find()->id($assetId)->one();

        if (!$asset) {
            return $this->asErrorJson(Craft::t('app', 'Asset not found with that id'));
        }

        $previewHtml = null;

        // todo: we should be passing the asset into getPreviewHtml(), not the constructor
        $previewHandler = Craft::$app->getAssets()->getAssetPreviewHandler($asset);
        if ($previewHandler) {
            try {
                $previewHtml = $previewHandler->getPreviewHtml();
            } catch (NotSupportedException $e) {
                // No big deal
            }
        }

        $view = $this->getView();

        return $this->asJson([
            'success' => true,
            'previewHtml' => $previewHtml,
            'headHtml' => $view->getHeadHtml(),
            'footHtml' => $view->getBodyHtml(),
            'requestId' => $requestId,
        ]);
    }

    /**
     * Sends a broken image response based on a given exception.
     *
     * @param \Throwable|null $e The exception that was thrown
     * @return Response
     * @since 3.4.8
     */
    protected function asBrokenImage(\Throwable $e = null): Response
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
     * @since 3.4.8
     */
    protected function requireVolumePermissionByAsset(string $permissionName, Asset $asset)
    {
        if (!$asset->getVolumeId()) {
            $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
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
    protected function requirePeerVolumePermissionByAsset(string $permissionName, Asset $asset)
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
     * @since 3.4.8
     */
    protected function requireVolumePermissionByFolder(string $permissionName, VolumeFolder $folder)
    {
        if (!$folder->volumeId) {
            $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            // Skip permission check only if it's the user's temporary folder
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
    protected function requireVolumePermission(string $permissionName, string $volumeUid)
    {
        $this->requirePermission($permissionName . ':' . $volumeUid);
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
