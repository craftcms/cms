<?php
namespace Craft;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note that all actions in the controller except {@link actionGenerateTransform} require an authenticated Craft session
 * via {@link BaseController::allowAnonymous}.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.controllers
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
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
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
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
	 * @return null
	 */
	public function actionUploadFile()
	{
		$this->requireAjaxRequest();
		$folderId = craft()->request->getPost('folderId');

		// Conflict resolution data
		$userResponse = craft()->request->getPost('userResponse');
		$theNewFileId = craft()->request->getPost('newFileId', 0);
		$fileName = craft()->request->getPost('fileName');

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

		$response = craft()->assets->uploadFile($folderId, $userResponse, $theNewFileId, $fileName);

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
		$fieldId = craft()->request->getPost('fieldId');
		$elementId = craft()->request->getPost('elementId');

		if (empty($_FILES['files']) || !isset($_FILES['files']['error'][0]) || $_FILES['files']['error'][0] != 0)
		{
			throw new Exception(Craft::t('The upload failed.'));
		}

		$field = craft()->fields->populateFieldType(craft()->fields->getFieldById($fieldId));

		if (!($field instanceof AssetsFieldType))
		{
			throw new Exception(Craft::t('That is not an Assets field.'));
		}

		if ($elementId)
		{
			$field->element = craft()->elements->getElementById($elementId);
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

		$response = craft()->assets->insertFileByLocalPath($fileLocation, $fileName, $targetFolderId, AssetConflictResolution::KeepBoth);
		$fileId = $response->getDataItem('fileId');

		// Render and return
		$element = craft()->elements->getElementById($fileId);
		$html = craft()->templates->render('_elements/element', array('element' => $element));
		$css = craft()->templates->getHeadHtml();

		$this->returnJson(array('html' => $html, 'css' => $css));
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
		$parentId = craft()->request->getRequiredPost('parentId');
		$folderName = craft()->request->getRequiredPost('folderName');

		try
		{
			craft()->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->createFolder($parentId, $folderName);

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
		$folderId = craft()->request->getRequiredPost('folderId');
		$response = craft()->assets->deleteFolderById($folderId);

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

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

		$folderId = craft()->request->getRequiredPost('folderId');
		$newName = craft()->request->getRequiredPost('newName');

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
			craft()->assets->checkPermissionByFolderIds($folderId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->renameFolder($folderId, $newName);

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
		$fileIds = craft()->request->getRequiredPost('fileId');

		try
		{
			craft()->assets->checkPermissionByFileIds($fileIds, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->deleteFiles($fileIds);
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

		$fileIds = craft()->request->getRequiredPost('fileId');
		$folderId = craft()->request->getRequiredPost('folderId');
		$fileName = craft()->request->getPost('fileName');
		$actions = craft()->request->getPost('action');

		try
		{
			craft()->assets->checkPermissionByFileIds($fileIds, 'removeFromAssetSource');
			craft()->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->moveFiles($fileIds, $folderId, $fileName, $actions);
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

		$folderId = craft()->request->getRequiredPost('folderId');
		$parentId = craft()->request->getRequiredPost('parentId');
		$action = craft()->request->getPost('action');

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
			craft()->assets->checkPermissionByFolderIds($parentId, 'uploadToAssetSource');
			craft()->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->moveFolder($folderId, $parentId, $action);

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
		$transformId = craft()->request->getQuery('transformId');
		$returnUrl = (bool) craft()->request->getPost('returnUrl', false);

		// If transform Id was not passed in, see if file id and handle were.
		if (empty($transformId))
		{
			$fileId = craft()->request->getPost('fileId');
			$handle = craft()->request->getPost('handle');
			$fileModel = craft()->assets->getFileById($fileId);
			$transformIndexModel = craft()->assetTransforms->getTransformIndex($fileModel, $handle);
		}
		else
		{
			$transformIndexModel = craft()->assetTransforms->getTransformIndexModelById($transformId);
		}

		try
		{
			$url = craft()->assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);
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
		craft()->end();
	}

	/**
	 * Get information about available transforms.
	 *
	 * @return null
	 */
	public function actionGetTransformInfo()
	{
		$this->requireAjaxRequest();
		$transforms = craft()->assetTransforms->getAllTransforms();
		$output = array();
		foreach ($transforms as $transform)
		{
			$output[] = (object) array('id' => $transform->id, 'handle' => HtmlHelper::encode($transform->handle), 'name' => HtmlHelper::encode($transform->name));
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
		$folder = craft()->assets->getFolderById($folderId);

		// if folder exists and the source ID is null, it's a temp source and we always allow uploads there.
		if (!(is_object($folder) && is_null($folder->sourceId)))
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
		}
	}
}

