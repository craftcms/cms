<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\AssetConflictException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\Exception;
use craft\app\errors\FileException;
use craft\app\errors\HttpException;
use craft\app\errors\AssetException;
use craft\app\errors\AssetMissingException;
use craft\app\errors\UploadFailedException;
use craft\app\fields\Assets as AssetsField;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\ImageHelper;
use craft\app\helpers\IOHelper;
use craft\app\elements\Asset;
use craft\app\helpers\StringHelper;
use craft\app\models\VolumeFolder;
use craft\app\web\Controller;
use craft\app\web\UploadedFile;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note that all actions in the controller except [[actionGenerateTransform]] require an authenticated Craft session
 * via [[Controller::allowAnonymous]].
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
	protected $allowAnonymous = ['generate-transform'];

	// Public Methods
	// =========================================================================

	/**
	 * Upload a file
	 *
	 * @throws HttpException
	 * @throws \Exception
	 * @return null
	 */
	public function actionSaveAsset()
	{
		$this->requireAjaxRequest();

		$uploadedFile       = UploadedFile::getInstanceByName('assets-upload');
		$fileId             = Craft::$app->getRequest()->getBodyParam('fileId');
		$folderId           = Craft::$app->getRequest()->getBodyParam('folderId');
		$fieldId            = Craft::$app->getRequest()->getBodyParam('fieldId');
		$elementId          = Craft::$app->getRequest()->getBodyParam('elementId');
		$conflictResolution = Craft::$app->getRequest()->getBodyParam('userResponse');

		$newFile = (bool) $uploadedFile && empty($fileId);
		$resolveConflict = !empty($conflictResolution) && !empty($fileId);

		// TODO Permission check
		try
		{
			// Resolving a conflict?
			if ($resolveConflict)
			{
				// When resolving a conflict, $fileId is the id of the file that was created
				// and is conflicting with an existing file.
				if ($conflictResolution == 'replace')
				{
					$fileToReplaceWith = Craft::$app->getAssets()->getFileById($fileId);

					$filename = AssetsHelper::prepareAssetName(Craft::$app->getRequest()->getRequiredBodyParam('filename'));
					$fileToReplace = Craft::$app->getAssets()->findFile(array('filename' => $filename, 'folderId' => $fileToReplaceWith->folderId));

					Craft::$app->getAssets()->replaceAsset($fileToReplace, $fileToReplaceWith);
				}
				else if ($conflictResolution == 'cancel')
				{
					Craft::$app->getAssets()->deleteFilesByIds($fileId);
				}

				return $this->asJson(['success' => true]);
			}
			else if ($newFile)
			{
				if ($uploadedFile->hasError)
				{
					throw new UploadFailedException($uploadedFile->error);
				}

				if (empty($folderId) && (empty($fieldId) || empty($elementId)))
				{
					throw new HttpException(400, Craft::t('app', 'No target destination provided for uploading.'));
				}

				if (empty($folderId))
				{
					$field = Craft::$app->getFields()->getFieldById($fieldId);

					if (!($field instanceof AssetsField))
					{
						throw new HttpException(400, Craft::t('app', 'The field provided is not an Assets field.'));
					}

					$element = $elementId ? Craft::$app->getElements()->getElementById($elementId) : null;
					$folderId = $field->resolveDynamicPath($element);
				}

				if (empty($folderId))
				{
					throw new HttpException(400, Craft::t('app', 'The target destination provided for uploading is not valid.'));
				}

				$folder = Craft::$app->getAssets()->findFolder(['id' => $folderId]);

				if (!$folder)
				{
					throw new HttpException(400, Craft::t('app', 'The target folder provided for uploading is not valid.'));
				}

				$pathOnServer = IOHelper::getTempFilePath($uploadedFile->name);
				$result = $uploadedFile->saveAs($pathOnServer);

				if (!$result)
				{
					IOHelper::deleteFile($pathOnServer, true);
					throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
				}

				$asset = new Asset();
				$asset->newFilePath = $pathOnServer;
				$asset->filename    = $uploadedFile->name;
				$asset->folderId    = $folder->id;
				$asset->volumeId    = $folder->volumeId;

				try
				{
					Craft::$app->getAssets()->saveAsset($asset);
					IOHelper::deleteFile($pathOnServer, true);
				}
				catch (AssetConflictException $exception)
				{
					// Okay, get a replacement name and re-save Asset.
					$replacementName = Craft::$app->getAssets()->getNameReplacementInFolder($asset->filename, $folder);
					$asset->filename = $replacementName;

					Craft::$app->getAssets()->saveAsset($asset);
					IOHelper::deleteFile($pathOnServer, true);

					return $this->asJson(['prompt' => true, 'fileId' => $asset->id, 'filename' => $uploadedFile->name]);
				}
				// No matter what happened, delete the file on server.
				catch (\Exception $exception)
				{
					IOHelper::deleteFile($pathOnServer, true);
					throw $exception;
				}

				return $this->asJson(['success' => true, 'filename' => $asset->filename]);
			}
			else
			{
				throw new HttpException(400);
			}
		}
		catch (\Exception $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}
	}

	/**
	 * Replace a file
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionReplaceFile()
	{
		$this->requireAjaxRequest();
		$fileId = Craft::$app->getRequest()->getBodyParam('fileId');
		$uploadedFile  = UploadedFile::getInstanceByName('replaceFile');

		try
		{
			// TODO check permissions
			if ($uploadedFile->hasError)
			{
				throw new UploadFailedException($uploadedFile->error);
			}

			$fileName = AssetsHelper::prepareAssetName($uploadedFile->name);
			$pathOnServer = IOHelper::getTempFilePath($uploadedFile->name);
			$result = $uploadedFile->saveAs($pathOnServer);

			if (!$result)
			{
				IOHelper::deleteFile($pathOnServer, true);
				throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
			}

			Craft::$app->getAssets()->replaceAssetFile($fileId, $pathOnServer, $fileName);
		}
		catch (Exception $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}

		return $this->asJson(['success' => true, 'fileId' => $fileId]);

	}

	/**
	 * Create a folder.
	 *
	 * @return null
	 * @throws HttpException
	 */
	public function actionCreateFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$parentId = Craft::$app->getRequest()->getRequiredBodyParam('parentId');
		$folderName = Craft::$app->getRequest()->getRequiredBodyParam('folderName');

		$folderName = AssetsHelper::prepareAssetName($folderName, false);

		// TODO Permission check

		try
		{
			$parentFolder = Craft::$app->getAssets()->findFolder(['id' => $parentId]);

			if (!$parentFolder)
			{
				throw new HttpException(400, Craft::t('app', 'The parent folder cannot be found.'));
			}

			$folderModel = new VolumeFolder();
			$folderModel->name     = $folderName;
			$folderModel->parentId = $parentId;
			$folderModel->volumeId = $parentFolder->volumeId;
			$folderModel->path     = $parentFolder->path . $folderName .'/';

			Craft::$app->getAssets()->createFolder($folderModel);

			return $this->asJson([
				'success' => true,
				'folderName' => $folderModel->name,
				'folderId' => $folderModel->id
			]);
		}
		catch (AssetException $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}


	}

	/**
	 * Delete a folder.
	 *
	 * @return null
	 */
	public function actionDeleteFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');

		// TODO permission checks
		try
		{
			Craft::$app->getAssets()->deleteFoldersByIds($folderId);
		}
		catch (AssetException $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}

		return $this->asJson(['success' => true]);

	}

	/**
	 * Rename a folder
	 *
	 * @return null
	 */
	public function actionRenameFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');
		$newName = Craft::$app->getRequest()->getRequiredBodyParam('newName');

		try
		{
			Craft::$app->getAssets()->checkPermissionByFolderIds($folderId, 'removeFromAssetVolume');
			Craft::$app->getAssets()->checkPermissionByFolderIds($folderId, 'createSubfoldersInAssetVolume');
		}
		catch (Exception $e)
		{
			return $this->asErrorJson($e->getMessage());
		}

		$response = Craft::$app->getAssets()->renameFolder($folderId, $newName);

		return $this->asJson($response->getResponseData());
	}



	/**
	 * Move a file or multiple files.
	 *
	 * @return null
	 */
	public function actionMoveFile()
	{
		$this->requireLogin();

		$fileId             = Craft::$app->getRequest()->getRequiredBodyParam('fileId');
		$folderId           = Craft::$app->getRequest()->getBodyParam('folderId');
		$filename           = Craft::$app->getRequest()->getBodyParam('filename');
		$conflictResolution = Craft::$app->getRequest()->getBodyParam('userResponse');

		// TODO permission checks
		try
		{
			$asset = Craft::$app->getAssets()->getFileById($fileId);

			if (empty($asset))
			{
				throw new AssetMissingException(Craft::t('app', 'The Asset is missing.'));
			}

			if (!empty($filename))
			{
				Craft::$app->getAssets()->renameAsset($asset, $filename);

				return $this->asJson(['success' => true]);
			}
			else
			{
				if ($asset->folderId != $folderId)
				{
					if (!empty($conflictResolution))
					{
						$conflictingAsset = Craft::$app->getAssets()->findFile(['filename' => $asset->filename, 'folderId' => $folderId]);

						if ($conflictResolution == 'replace')
						{
							Craft::$app->getAssets()->replaceAsset($conflictingAsset, $asset, true);
						}
						else if ($conflictResolution == 'keepBoth')
						{
							$targetFolder = Craft::$app->getAssets()->getFolderById($folderId);
							$newFilename = Craft::$app->getAssets()->getNameReplacementInFolder($asset->filename, $targetFolder);
							Craft::$app->getAssets()->moveAsset($asset, $folderId, $newFilename);
						}
					}
					else
					{
						try
						{
							Craft::$app->getAssets()->moveAsset($asset, $folderId);
						}
						catch (AssetConflictException $exception)
						{
							return $this->asJson(['prompt' => true, 'filename' => $asset->filename, 'fileId' => $asset->id]);
						}
					}
				}
			}
		}
		catch (\Exception $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}

		return $this->asJson(['success' => true]);
	}

	/**
	 * Move a folder.
	 *
	 * @return null
	 */
	public function actionMoveFolder()
	{
		$this->requireLogin();

		$folderToMoveId     = Craft::$app->getRequest()->getRequiredBodyParam('folderId');
		$newParentFolderId  = Craft::$app->getRequest()->getRequiredBodyParam('parentId');
		$conflictResolution = Craft::$app->getRequest()->getBodyParam('userResponse');

		// TODO permission checks
		try
		{
			$folderToMove      = Craft::$app->getAssets()->getFolderById($folderToMoveId);
			$destinationFolder = Craft::$app->getAssets()->getFolderById($newParentFolderId);
			$removeFromTree = [];

			if (empty($folderToMove))
			{
				throw new AssetLogicException(Craft::t('app', 'The folder you are trying to move does not exist!'));
			}

			if (empty($destinationFolder))
			{
				throw new AssetLogicException(Craft::t('app', 'The destination folder does not exist!'));
			}

			$sourceTree = Craft::$app->getAssets()->getAllDescendantFolders($folderToMove);

			if (empty($conflictResolution))
			{
				$existingFolder = Craft::$app->getAssets()->findFolder(['parentId' => $newParentFolderId, 'name' => $folderToMove->name]);

				if ($existingFolder)
				{
					// Throw a prompt
					return $this->asJson(['prompt' => true, 'foldername' => $folderToMove->name, 'folderId' => $folderToMoveId, 'parentId' => $newParentFolderId]);
				}
				else
				{
					// No conflicts, mirror the existing structure
					$folderIdChanges = AssetsHelper::mirrorFolderStructure($folderToMove, $destinationFolder);

					// Get the file transfer list.
					$allSourceFolderIds = array_keys($sourceTree);
					$allSourceFolderIds[] = $folderToMoveId;
					$assets = Craft::$app->getAssets()->findFiles(['folderId' => $allSourceFolderIds]);
					$fileTransferList = AssetsHelper::getFileTransferList($assets, $folderIdChanges, $conflictResolution == 'merge');
				}
			}
			else
			{
				// Resolving a confclit
				$existingFolder = Craft::$app->getAssets()->findFolder(['parentId' => $newParentFolderId, 'name' => $folderToMove->name]);
				$targetTreeMap = [];

				// When merging folders, make sure that we're not overwriting folders
				if ($conflictResolution == 'merge')
				{
					$targetTree = Craft::$app->getAssets()->getAllDescendantFolders($existingFolder);
					$targetPrefixLength = strlen($destinationFolder->path);
					$targetTreeMap = [];

					foreach ($targetTree as $existingFolder)
					{
						$targetTreeMap[substr($existingFolder->path, $targetPrefixLength)] = $existingFolder->id;
					}

					$removeFromTree = [$existingFolder->id];
				}
				// When replacing, just nuke everything that's in our way
				else if ($conflictResolution == 'replace')
				{
					$removeFromTree = [$existingFolder->id];
					Craft::$app->getAssets()->deleteFoldersByIds($existingFolder->id);
				}

				// Mirror the structure, passing along the exsting folder map
				$folderIdChanges = AssetsHelper::mirrorFolderStructure($folderToMove, $destinationFolder, $targetTreeMap);

				// Get file transfer list for the progress bar
				$allSourceFolderIds = array_keys($sourceTree);
				$allSourceFolderIds[] = $folderToMoveId;
				$assets = Craft::$app->getAssets()->findFiles(['folderId' => $allSourceFolderIds]);
				$fileTransferList = AssetsHelper::getFileTransferList($assets, $folderIdChanges, $conflictResolution == 'merge');
			}
		}
		catch (AssetLogicException $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}

		return $this->asJson(['success' => true, 'changedIds' => $folderIdChanges, 'transferList' => $fileTransferList, 'removeFromTree' => $removeFromTree]);

	}

	/**
	 * Generate a transform.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionGenerateTransform()
	{
		$transformId = Craft::$app->getRequest()->getQuery('transformId');
		$returnUrl = (bool) Craft::$app->getRequest()->getBodyParam('returnUrl', false);

		// If transform Id was not passed in, see if file id and handle were.
		if (empty($transformId))
		{
			$fileId = Craft::$app->getRequest()->getBodyParam('fileId');
			$handle = Craft::$app->getRequest()->getBodyParam('handle');
			$fileModel = Craft::$app->getAssets()->getFileById($fileId);
			$transformIndexModel = Craft::$app->getAssetTransforms()->getTransformIndex($fileModel, $handle);
		}
		else
		{
			$transformIndexModel = Craft::$app->getAssetTransforms()->getTransformIndexModelById($transformId);
		}

		try
		{
			$url = Craft::$app->getAssetTransforms()->ensureTransformUrlByIndexModel($transformIndexModel);
		}
		catch (Exception $exception)
		{
			throw new HttpException(404, $exception->getMessage());
		}

		if ($returnUrl)
		{
			return $this->asJson(['url' => $url]);
		}

		return $this->redirect($url);
	}

	/**
	 * Get information about available transforms.
	 *
	 * @return null
	 */
	public function actionGetTransformInfo()
	{
		$this->requireAjaxRequest();
		$transforms = Craft::$app->getAssetTransforms()->getAllTransforms();
		$output = [];
		foreach ($transforms as $transform)
		{
			$output[] = (object) ['id' => $transform->id, 'handle' => $transform->handle, 'name' => $transform->name];
		}

		return $this->asJson($output);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Check upload permissions.
	 *
	 * @param $folderId
	 *
	 * @return null
	 */
	private function _checkUploadPermissions($folderId)
	{
		$folder = Craft::$app->getAssets()->getFolderById($folderId);

		// if folder exists and the volume ID is null, it's a temp volume and we always allow uploads there.
		if (!(is_object($folder) && is_null($folder->volumeId)))
		{
			Craft::$app->getAssets()->checkPermissionByFolderIds($folderId, 'uploadToAssetVolume');
		}
	}
}

