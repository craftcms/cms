<?php
namespace Craft;

/**
 * Handles asset tasks
 * TODO: Permissions?
 */
class AssetsController extends BaseController
{
	protected $allowAnonymous = array('actionGenerateTransform');

	/**
	 * Saves the asset field layout.
	 */
	public function actionSaveFieldLayout()
	{
		$this->requirePostRequest();
		craft()->userSession->requireAdmin();

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::Asset;
		craft()->fields->deleteLayoutsByType(ElementType::Asset);

		if (craft()->fields->saveLayout($fieldLayout, false))
		{
			craft()->userSession->setNotice(Craft::t('Asset fields saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save asset fields.'));
		}
	}

	/**
	 * Upload a file
	 */
	public function actionUploadFile()
	{
		$this->requireAjaxRequest();
		$folderId = craft()->request->getQuery('folderId');

		// Conflict resolution data
		$userResponse = craft()->request->getPost('userResponse');
		$responseInfo = craft()->request->getPost('additionalInfo');
		$fileName = craft()->request->getPost('fileName');

		$response = craft()->assets->uploadFile($folderId, $userResponse, $responseInfo, $fileName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * View a file's content.
	 */
	public function actionEditFileContent()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$requestId = craft()->request->getPost('requestId', 0);
		$fileId = craft()->request->getRequiredPost('elementId');
		$file = craft()->assets->getFileById($fileId);

		if (!$file)
		{
			throw new Exception(Craft::t('No asset exists with the ID “{id}”.', array('id' => $fileId)));
		}

		$html = craft()->templates->render('_includes/edit_element', array(
			'element'     => $file,
			'hasTitle'    => true,
			'fieldLayout' => craft()->fields->getLayoutByType(ElementType::Asset)
		));

		$this->returnJson(array(
			'requestId' => $requestId,
			'headHtml' => craft()->templates->getHeadHtml(),
			'bodyHtml' => $html,
			'footHtml' => craft()->templates->getFootHtml(),
		));
	}

	/**
	 * Save a file's content.
	 */
	public function actionSaveFileContent()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$fileId = craft()->request->getRequiredPost('elementId');
		$file = craft()->assets->getFileById($fileId);

		if (!$file)
		{
			throw new Exception(Craft::t('No asset exists with the ID “{id}”.', array('id' => $fileId)));
		}

		$title = craft()->request->getPost('title');
		$file->getContent()->title = $title;

		$fields = craft()->request->getPost('fields');
		$file->getContent()->setAttributes($fields);

		$success = craft()->assets->saveFileContent($file);

		$this->returnJson(array(
			'success' => $success,
			'title'   => $title
		));
	}

	/**
	 * Create a folder.
	 */
	public function actionCreateFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$parentId = craft()->request->getRequiredPost('parentId');
		$folderName = craft()->request->getRequiredPost('folderName');

		$response = craft()->assets->createFolder($parentId, $folderName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Delete a folder.
	 */
	public function actionDeleteFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$folderId = craft()->request->getRequiredPost('folderId');
		$response = craft()->assets->deleteFolderById($folderId);

		$this->returnJson($response->getResponseData());

	}

	/**
	 * Rename a folder
	 */
	public function actionRenameFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$folderId = craft()->request->getRequiredPost('folderId');
		$newName = craft()->request->getRequiredPost('newName');

		$response = craft()->assets->renameFolder($folderId, $newName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Delete a file or multiple files.
	 */
	public function actionDeleteFile()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$fileIds = craft()->request->getRequiredPost('fileId');

		$response = craft()->assets->deleteFiles($fileIds);
		$this->returnJson($response->getResponseData());
	}

	/**
	 * Move a file or multiple files.
	 */
	public function actionMoveFile()
	{
		$fileIds = craft()->request->getRequiredPost('fileId');
		$folderId = craft()->request->getRequiredPost('folderId');
		$fileName = craft()->request->getPost('fileName');
		$actions = craft()->request->getPost('action');

		$response = craft()->assets->moveFiles($fileIds, $folderId, $fileName, $actions);
		$this->returnJson($response->getResponseData());
	}

	/**
	 * Move a folder.
	 */
	public function actionMoveFolder()
	{
		$folderId = craft()->request->getRequiredPost('folderId');
		$parentId = craft()->request->getRequiredPost('parentId');
		$action = craft()->request->getPost('action');

		$response = craft()->assets->moveFolder($folderId, $parentId, $action);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Generate a transform.
	 */
	public function actionGenerateTransform()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$transformId = craft()->request->getRequiredPost('transformId');

		$transformIndexModel = craft()->assetTransforms->getTransformIndexModelById($transformId);

		if (!$transformIndexModel)
		{
			throw new Exception(Craft::t('No asset image transform exists with the ID “{id}”', array('id' => $transformId)));
		}

		if ($transformIndexModel->inProgress)
		{
			echo 'working';
			craft()->end();
		}
		else
		{
			if (!$transformIndexModel->fileExists)
			{
				$transformIndexModel->inProgress = 1;
				craft()->assetTransforms->storeTransformIndexData($transformIndexModel);

				craft()->assetTransforms->generateTransform($transformIndexModel);

				$transformIndexModel->inProgress = 0;
				$transformIndexModel->fileExists = 1;
				craft()->assetTransforms->storeTransformIndexData($transformIndexModel);
			}

			echo 'success:'.craft()->assetTransforms->getUrlforTransformByIndexId($transformId);
			craft()->end();
		}
	}
}

