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
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.controllers
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
 */
class AssetsController extends BaseController
{
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

		IOHelper::deleteFile($fileLocation, true);

		if ($response->isError())
		{
			$this->returnErrorJson($response->getAttribute('errorMessage'));
		}

		$fileId = $response->getDataItem('fileId');

		// Render and return
		$element = craft()->elements->getElementById($fileId);
		$html = craft()->templates->render('_elements/element', array('element' => $element));
		$headHtml = craft()->templates->getHeadHtml();

		$this->returnJson(array('html' => $html, 'headHtml' => $headHtml));
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
		$fileId = craft()->request->getPost('fileId');

		try
		{
			if (empty($_FILES['replaceFile']) || !isset($_FILES['replaceFile']['error']) || $_FILES['replaceFile']['error'] != 0)
			{
				throw new Exception(Craft::t('The upload failed.'));
			}

			$existingFile = craft()->assets->getFileById($fileId);

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

			// Fire an 'onBeforeReplaceFile' event
			$event = new Event($this, array(
				'asset' => $existingFile
			));

			craft()->assets->onBeforeReplaceFile($event);

			// Is the event preventing this from happening?
			if (!$event->performAction)
			{
				throw new Exception(Craft::t('The file could not be replaced.'));
			}

			$fileName = AssetsHelper::cleanAssetName($_FILES['replaceFile']['name']);
			$fileLocation = AssetsHelper::getTempFilePath(pathinfo($fileName, PATHINFO_EXTENSION));
			move_uploaded_file($_FILES['replaceFile']['tmp_name'], $fileLocation);

			$response = craft()->assets->insertFileByLocalPath($fileLocation, $fileName, $targetFolderId, AssetConflictResolution::KeepBoth);
			$insertedFileId = $response->getDataItem('fileId');

			$newFile = craft()->assets->getFileById($insertedFileId);

			if ($newFile && $existingFile)
			{
				$source = craft()->assetSources->populateSourceType($newFile->getSource());

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
				IOHelper::deleteFile($fileLocation, true);
				throw new Exception(Craft::t('Something went wrong with the replace operation.'));
			}
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		// Fire an 'onReplaceFile' event
		craft()->assets->onReplaceFile(new Event($this, array(
			'asset' => $existingFile
		)));

		$this->returnJson(array('success' => true, 'fileId' => $fileId));
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

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->deleteFolderById($folderId);

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
	 * Download an Asset.
	 *
	 * @throws HttpException
	 */
	public function actionDownloadAsset()
	{
		$this->requireLogin();
		$this->requirePostRequest();

		$assetId = craft()->request->getRequiredPost('assetId');

		try
		{
			craft()->assets->checkPermissionByFileIds($assetId, 'viewAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$asset = craft()->assets->getFileById($assetId);

		if (!$asset)
		{
			throw new HttpException(404);
		}

		$source = craft()->assetSources->populateSourceType($asset->getSource());

		$localPath = $source->getLocalCopy($asset);

		craft()->request->sendFile($localPath, IOHelper::getFileContents($localPath), array('filename' => $asset->filename), false);
		IOHelper::deleteFile($localPath);
		craft()->end();
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

