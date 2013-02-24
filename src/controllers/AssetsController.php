<?php
namespace Craft;

/**
 * Handles asset tasks
 * TODO: Permissions?
 */
class AssetsController extends BaseController
{
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

		$this->renderRequestedTemplate();
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
	 * View a folder
	 */
	public function actionViewFolder()
	{
		$this->requireAjaxRequest();
		$folderId = craft()->request->getRequiredPost('folderId');
		$requestId = craft()->request->getPost('requestId', 0);
		$viewType = craft()->request->getPost('viewType', 'thumbs');

		$folder = craft()->assets->getFolderById($folderId);
		$files = craft()->assets->getFilesByFolderId($folderId);


		$subfolders = craft()->assets->findFolders(array(
			'parentId' => $folderId
		));

		$html = craft()->templates->render('assets/_views/folder_contents',
			array(
				'folder' => $folder,
				'subfolders' => $subfolders,
				'view' => $viewType,
				'files' => $files
			)
		);

		$this->returnJson(array(
			'requestId' => $requestId,
			'html' => $html
		));
	}

	/**
	 * View a file's content.
	 */
	public function actionViewFile()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$requestId = craft()->request->getPost('requestId', 0);
		$fileId = craft()->request->getRequiredPost('fileId');
		$file = craft()->assets->getFileById($fileId);

		if (!$file)
		{
			throw new Exception(Craft::t('No asset exists with the ID “{id}”.', array('id' => $fileId)));
		}

		$html = craft()->templates->render('assets/_views/file', array(
			'file' => $file
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

		$fileId = craft()->request->getRequiredPost('fileId');
		$file = craft()->assets->getFileById($fileId);

		if (!$file)
		{
			throw new Exception(Craft::t('No asset exists with the ID “{id}”.', array('id' => $fileId)));
		}

		$fields = craft()->request->getPost('fields', array());
		$file->setContent($fields);

		$success = craft()->assets->saveFileContent($file);
		$this->returnJson(array('success' => $success));
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
		$response = craft()->assets->deleteFolder($folderId);

		$this->returnJson($response->getResponseData());

	}
}
