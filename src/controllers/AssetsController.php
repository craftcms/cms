<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\errors\FileException;
use craft\app\errors\AssetException;
use craft\app\errors\AssetMissingException;
use craft\app\errors\ModelException;
use craft\app\errors\ElementException;
use craft\app\errors\UploadFailedException;
use craft\app\fields\Assets as AssetsField;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\IOHelper;
use craft\app\elements\Asset;
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

		$file               = UploadedFile::getInstanceByName('assets-upload');
		$fileId             = Craft::$app->getRequest()->getBodyParam('fileId');;
		$folderId           = Craft::$app->getRequest()->getBodyParam('folderId');
		$fieldId            = Craft::$app->getRequest()->getBodyParam('fieldId');
		$elementId          = Craft::$app->getRequest()->getBodyParam('elementId');
		$conflictResolution = Craft::$app->getRequest()->getBodyParam('conflictResolution');

		$newFile = (bool) $file && empty($fileId);
		$resolveConflict = !empty($conflictResolution) && !empty($fileId);

		// TODO Permission check
		try
		{
			// Resolving a conflict?
			if ($resolveConflict)
			{
				// Determine type and resolve
			}
			else if ($newFile)
			{
				if ($file->hasError)
				{
					throw new UploadFailedException($file->error);
				}

				if (empty($folderId) && (empty($fieldId) || empty($elementId)))
				{
					throw new HttpException(400, Craft::t('app', 'No target destination provided for uploading.'));
				}

				if (empty($folderId))
				{
					$field = Craft::$app->fields->getFieldById($fieldId);

					if (!($field instanceof AssetsField))
					{
						throw new HttpException(400, Craft::t('app', 'The field provided is not an Assets field.'));
					}

					$element = $elementId ? Craft::$app->elements->getElementById($elementId) : null;
					$folderId = $field->resolveDynamicPath($element);
				}

				if (empty($folderId))
				{
					throw new HttpException(400, Craft::t('app', 'The target destination provided for uploading is not valid.'));
				}

				$folder = Craft::$app->assets->findFolder(['id' => $folderId]);

				if (!$folder)
				{
					throw new HttpException(400, Craft::t('app', 'The target folder provided for uploading is not valid.'));
				}

				$pathOnServer = IOHelper::getTempFilePath($file->name);
				$result = $file->saveAs($pathOnServer);

				if (!$result)
				{
					IOHelper::deleteFile($pathOnServer, true);
					throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
				}

				try
				{
					$asset = new Asset();

					$asset->newFilePath = $pathOnServer;
					$asset->filename    = $file->name;
					$asset->folderId    = $folder->id;
					$asset->volumeId    = $folder->volumeId;

					Craft::$app->assets->saveAsset($asset);

					IOHelper::deleteFile($pathOnServer, true);
				}
					// No matter what happened, delete the file on server.
				catch (\Exception $exception)
				{
					IOHelper::deleteFile($pathOnServer, true);
					throw $exception;
				}

				$this->returnJson(['success' => true, 'filename' => $asset->filename]);
			}
			else
			{
				throw new HttpException(400);
			}
		}
		catch (FileException $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
		catch (ElementException $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
		catch (ModelException $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
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
			$parentFolder = Craft::$app->assets->findFolder(['id' => $parentId]);

			if (!$parentFolder)
			{
				throw new HttpException(400, Craft::t('app', 'The parent folder cannot be found.'));
			}

			$folderModel = new VolumeFolder();
			$folderModel->name     = $folderName;
			$folderModel->parentId = $parentId;
			$folderModel->volumeId = $parentFolder->volumeId;
			$folderModel->path     = $parentFolder->path . $folderName .'/';

			Craft::$app->assets->createFolder($folderModel);

			$this->returnJson([
				'success' => true,
				'folderName' => $folderModel->name,
				'folderId' => $folderModel->id
			]);
		}
		catch (AssetException $exception)
		{
			$this->returnErrorJson($exception->getMessage());
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
			Craft::$app->assets->deleteFoldersByIds($folderId);
		}
		catch (AssetException $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnJson(['success' => true]);

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
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetVolume');
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'createSubfoldersInAssetVolume');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = Craft::$app->assets->renameFolder($folderId, $newName);

		$this->returnJson($response->getResponseData());
	}



	/**
	 * Move a file or multiple files.
	 *
	 * @return null
	 */
	public function actionMoveFile()
	{
		$this->requireLogin();

		$fileIds            = Craft::$app->getRequest()->getRequiredBodyParam('fileId');
		$folderId           = Craft::$app->getRequest()->getBodyParam('folderId');
		$filename           = Craft::$app->getRequest()->getBodyParam('filename');
		$conflictResolution = Craft::$app->getRequest()->getBodyParam('conflictResolution');

		// TODO permission checks
		try
		{
			if (!empty($filename))
			{
				$file = Craft::$app->assets->getFileById($fileIds);

				if (empty($file))
				{
					throw new AssetMissingException(Craft::t('app', 'The Asset cannot is missing.'));
				}

				Craft::$app->assets->renameFile($file, $filename);

				$this->returnJson(['success' => true]);
			}
			else
			{
				// Move files.
			}
		}
		catch (AssetException $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnJson(['success' => true]);
	}

	/**
	 * Move a folder.
	 *
	 * @return null
	 */
	public function actionMoveFolder()
	{
		$this->requireLogin();

		$folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');
		$parentId = Craft::$app->getRequest()->getRequiredBodyParam('parentId');
		$action = Craft::$app->getRequest()->getBodyParam('action');

		try
		{
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetVolume');
			Craft::$app->assets->checkPermissionByFolderIds($parentId, 'uploadToAssetVolume');
			Craft::$app->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetVolume');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = Craft::$app->assets->moveFolder($folderId, $parentId, $action);

		$this->returnJson($response->getResponseData());
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
			$fileModel = Craft::$app->assets->getFileById($fileId);
			$transformIndexModel = Craft::$app->assetTransforms->getTransformIndex($fileModel, $handle);
		}
		else
		{
			$transformIndexModel = Craft::$app->assetTransforms->getTransformIndexModelById($transformId);
		}

		try
		{
			$url = Craft::$app->assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);
		}
		catch (Exception $exception)
		{
			throw new HttpException(404, $exception->getMessage());
		}

		if ($returnUrl)
		{
			$this->returnJson(['url' => $url]);
		}

		$this->redirect($url);
		Craft::$app->end();
	}

	/**
	 * Get information about available transforms.
	 *
	 * @return null
	 */
	public function actionGetTransformInfo()
	{
		$this->requireAjaxRequest();
		$transforms = Craft::$app->assetTransforms->getAllTransforms();
		$output = [];
		foreach ($transforms as $transform)
		{
			$output[] = (object) ['id' => $transform->id, 'handle' => $transform->handle, 'name' => $transform->name];
		}

		$this->returnJson($output);
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
		$folder = Craft::$app->assets->getFolderById($folderId);

		// if folder exists and the volume ID is null, it's a temp volume and we always allow uploads there.
		if (!(is_object($folder) && is_null($folder->volumeId)))
		{
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetVolume');
		}
	}
}

