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
use craft\app\events\AssetEvent;
use craft\app\fieldtypes\Assets;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\HtmlHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\services\Assets;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note that all actions in the controller except [[actionGenerateTransform]] require an authenticated Craft session
 * via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetsController extends BaseController
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
	 * login on a few, it's best to call [[requireLogin()]] in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = ['actionGenerateTransform'];

	// Public Methods
	// =========================================================================

	/**
	 * Upload a file
	 *
	 * @return null
	 */
	public function actionUploadFile()
	{
		$this->requireAjaxRequest();
		$folderId = Craft::$app->request->getBodyParam('folderId');

		// Conflict resolution data
		$userResponse = Craft::$app->request->getBodyParam('userResponse');
		$theNewFileId = Craft::$app->request->getBodyParam('newFileId', 0);
		$fileName = Craft::$app->request->getBodyParam('fileName');

		// For a conflict resolution, the folder ID is no longer there and no file is actually being uploaded
		if (!empty($folderId) && empty($userResponse))
		{
			try
			{
				$this->_checkUploadPermissions($folderId);
			}
			catch (Exception $e)
			{
				$this->returnErrorJson($e->getMessage());
			}
		}

		$response = Craft::$app->assets->uploadFile($folderId, $userResponse, $theNewFileId, $fileName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Uploads a file directly to a field for an entry.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionExpressUpload()
	{
		$this->requireAjaxRequest();
		$fieldId = Craft::$app->request->getBodyParam('fieldId');
		$elementId = Craft::$app->request->getBodyParam('elementId');

		if (empty($_FILES['files']) || !isset($_FILES['files']['error'][0]) || $_FILES['files']['error'][0] != 0)
		{
			throw new Exception(Craft::t('The upload failed.'));
		}

		$field = Craft::$app->fields->populateFieldType(Craft::$app->fields->getFieldById($fieldId));

		if (!($field instanceof Assets))
		{
			throw new Exception(Craft::t('That is not an Assets field.'));
		}

		if ($elementId)
		{
			$field->element = Craft::$app->elements->getElementById($elementId);
		}

		$targetFolderId = $field->resolveSourcePath();

		try
		{
			$this->_checkUploadPermissions($targetFolderId);
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$fileName = $_FILES['files']['name'][0];
		$fileLocation = AssetsHelper::getTempFilePath(pathinfo($fileName, PATHINFO_EXTENSION));
		move_uploaded_file($_FILES['files']['tmp_name'][0], $fileLocation);

		$response = Craft::$app->assets->insertFileByLocalPath($fileLocation, $fileName, $targetFolderId, AssetConflictResolution::KeepBoth);

		IOHelper::deleteFile($fileLocation, true);

		if ($response->isError())
		{
			$this->returnErrorJson($response->getAttribute('errorMessage'));
		}

		$fileId = $response->getDataItem('fileId');

		// Render and return
		$element = Craft::$app->elements->getElementById($fileId);
		$html = Craft::$app->templates->render('_elements/element', ['element' => $element]);
		$headHtml = Craft::$app->templates->getHeadHtml();

		$this->returnJson(['html' => $html, 'headHtml' => $headHtml]);
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
		$fileId = Craft::$app->request->getBodyParam('fileId');

		try
		{
			if (empty($_FILES['replaceFile']) || !isset($_FILES['replaceFile']['error']) || $_FILES['replaceFile']['error'] != 0)
			{
				throw new Exception(Craft::t('The upload failed.'));
			}

			$existingFile = Craft::$app->assets->getFileById($fileId);

			if (!$existingFile)
			{
				throw new Exception(Craft::t('The file to be replaced cannot be found.'));
			}

			$targetFolderId = $existingFile->folderId;

			try
			{
				$this->_checkUploadPermissions($targetFolderId);
			}
			catch (Exception $e)
			{
				$this->returnErrorJson($e->getMessage());
			}

			// Fire a 'beforeReplaceFile' event
			$event = new AssetEvent([
				'asset' => $existingFile
			]);

			Craft::$app->assets->trigger(Assets::EVENT_BEFORE_REPLACE_FILE, $event);

			// Is the event preventing this from happening?
			if (!$event->performAction)
			{
				throw new Exception(Craft::t('The file could not be replaced.'));
			}

			$fileName = $_FILES['replaceFile']['name'];
			$fileLocation = AssetsHelper::getTempFilePath(pathinfo($fileName, PATHINFO_EXTENSION));
			move_uploaded_file($_FILES['replaceFile']['tmp_name'], $fileLocation);

			$response = Craft::$app->assets->insertFileByLocalPath($fileLocation, $fileName, $targetFolderId, AssetConflictResolution::KeepBoth);
			$insertedFileId = $response->getDataItem('fileId');

			$newFile = Craft::$app->assets->getFileById($insertedFileId);

			if ($newFile && $existingFile)
			{
				$source = Craft::$app->assetSources->populateSourceType($newFile->getSource());

				if (StringHelper::toLowerCase($existingFile->filename) == StringHelper::toLowerCase($fileName))
				{
					$filenameToUse = $existingFile->filename;
				}
				else
				{
					// If the file uploaded had to resolve a conflict, grab the final filename
					if ($response->getDataItem('filename'))
					{
						$filenameToUse = $response->getDataItem('filename');
					}
					else
					{
						$filenameToUse = $fileName;
					}
				}

				$source->replaceFile($existingFile, $newFile, $filenameToUse);
				IOHelper::deleteFile($fileLocation, true);
			}
			else
			{
				throw new Exception(Craft::t('Something went wrong with the replace operation.'));
			}
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		// Fire an 'afterReplaceFile' event
		Craft::$app->assets->trigger(Assets::EVENT_AFTER_REPLACE_FILE, new AssetEvent([
			'asset' => $existingFile
		]));

		$this->returnJson(['success' => true, 'fileId' => $fileId]);
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
		$parentId = Craft::$app->request->getRequiredBodyParam('parentId');
		$folderName = Craft::$app->request->getRequiredBodyParam('folderName');

		try
		{
			Craft::$app->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = Craft::$app->assets->createFolder($parentId, $folderName);

		$this->returnJson($response->getResponseData());
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
		$folderId = Craft::$app->request->getRequiredBodyParam('folderId');

		try
		{
			Craft::$app->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = Craft::$app->assets->deleteFolderById($folderId);

		$this->returnJson($response->getResponseData());

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

		$folderId = Craft::$app->request->getRequiredBodyParam('folderId');
		$newName = Craft::$app->request->getRequiredBodyParam('newName');

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
	 * Delete a file or multiple files.
	 *
	 * @return null
	 */
	public function actionDeleteFile()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$fileIds = Craft::$app->request->getRequiredBodyParam('fileId');

		try
		{
			Craft::$app->assets->checkPermissionByFileIds($fileIds, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = Craft::$app->assets->deleteFiles($fileIds);
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

		$fileIds = Craft::$app->request->getRequiredBodyParam('fileId');
		$folderId = Craft::$app->request->getRequiredBodyParam('folderId');
		$fileName = Craft::$app->request->getBodyParam('fileName');
		$actions = Craft::$app->request->getBodyParam('action');

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

		$folderId = Craft::$app->request->getRequiredBodyParam('folderId');
		$parentId = Craft::$app->request->getRequiredBodyParam('parentId');
		$action = Craft::$app->request->getBodyParam('action');

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
		$transformId = Craft::$app->request->getQueryParam('transformId');
		$returnUrl = (bool) Craft::$app->request->getBodyParam('returnUrl', false);

		// If transform Id was not passed in, see if file id and handle were.
		if (empty($transformId))
		{
			$fileId = Craft::$app->request->getBodyParam('fileId');
			$handle = Craft::$app->request->getBodyParam('handle');
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
		$output = [];

		foreach ($transforms as $transform)
		{
			$output[] = (object) ['id' => $transform->id, 'handle' => HtmlHelper::encode($transform->handle), 'name' => HtmlHelper::encode($transform->name)];
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

