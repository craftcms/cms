<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\enums\AssetConflictResolution;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\errors\FileException;
use craft\app\errors\AssetException;
use craft\app\errors\ModelException;
use craft\app\errors\ElementException;
use craft\app\errors\UploadFailedException;
use craft\app\fieldtypes\Assets as AssetsFieldType;
use craft\app\events\AssetEvent;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\HtmlHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\Asset;
use craft\app\models\AssetFolder;
use craft\app\services\Assets as AssetsService;
use craft\app\web\Controller;
use craft\app\web\UploadedFile;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note that all actions in the controller except {@link actionGenerateTransform} require an authenticated Craft session
 * via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class AssetsController extends Controller
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() Craft::$app->userSession->requireLogin()}
	 * in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = array('actionGenerateTransform');

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
					$field = Craft::$app->fields->populateFieldType(Craft::$app->fields->getFieldById($fieldId));

					if (!($field instanceof AssetsFieldType))
					{
						throw new HttpException(400, Craft::t('app', 'The field provided is not an Assets field.'));
					}

					if ($elementId)
					{
						$field->element = Craft::$app->elements->getElementById($elementId);
					}

					$folderId = $field->resolveSourcePath();
				}

				if (empty($folderId))
				{
					throw new HttpException(400, Craft::t('app', 'The target destination provided for uploading is not valid.'));
				}

				$folder = Craft::$app->assets->findFolder(array('id' => $folderId));

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
					$asset->sourceId    = $folder->sourceId;

					Craft::$app->assets->saveAsset($asset);

					IOHelper::deleteFile($pathOnServer, true);
				}
					// No matter what happened, delete the file on server.
				catch (\Exception $exception)
				{
					IOHelper::deleteFile($pathOnServer, true);
					throw $exception;
				}

				$this->returnJson(array('success' => true, 'filename' => $asset->filename));
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
			$parentFolder = Craft::$app->assets->findFolder(array('id' => $parentId));

			if (!$parentFolder)
			{
				throw new HttpException(400, Craft::t('app', 'The parent folder cannot be found.'));
			}

			$folderModel = new AssetFolder();
			$folderModel->name     = $folderName;
			$folderModel->parentId = $parentId;
			$folderModel->sourceId = $parentFolder->sourceId;
			$folderModel->path     = $parentFolder->path . $folderName .'/';

			Craft::$app->assets->createFolder($folderModel);

			$this->returnJson(array('success' => true, 'folderName' => $folderModel->name, 'folderId' => $folderModel->id));
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

		$this->returnJson(array('success' => true));

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
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'createSubfoldersInAssetSource');
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

		$fileIds = Craft::$app->getRequest()->getRequiredBodyParam('fileId');
		$folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');
		$fileName = Craft::$app->getRequest()->getBodyParam('fileName');
		$actions = Craft::$app->getRequest()->getBodyParam('action');

		try
		{
			Craft::$app->assets->checkPermissionByFileIds($fileIds, 'removeFromAssetSource');
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = Craft::$app->assets->moveFiles($fileIds, $folderId, $fileName, $actions);
		$this->returnJson($response->getResponseData());
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
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
			Craft::$app->assets->checkPermissionByFolderIds($parentId, 'uploadToAssetSource');
			Craft::$app->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetSource');
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
		$transformId = Craft::$app->request->getQuery('transformId');
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
			$this->returnJson(array('url' => $url));
		}

		$this->redirect($url, true, 302);
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
		$output = array();
		foreach ($transforms as $transform)
		{
			$output[] = (object) array('id' => $transform->id, 'handle' => $transform->handle, 'name' => $transform->name);
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

		// if folder exists and the source ID is null, it's a temp source and we always allow uploads there.
		if (!(is_object($folder) && is_null($folder->sourceId)))
		{
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
		}
	}
}

