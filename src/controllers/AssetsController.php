<?php
namespace Blocks;

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
		blx()->userSession->requireAdmin();

		// Set the field layout
		$fieldLayout = blx()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::Asset;
		blx()->fields->deleteLayoutsByType(ElementType::Asset);

		if (blx()->fields->saveLayout($fieldLayout, false))
		{
			blx()->userSession->setNotice(Blocks::t('Asset fields saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save asset fields.'));
		}

		$this->renderRequestedTemplate();
	}

	/**
	 * Upload a file
	 */
	public function actionUploadFile()
	{
		$this->requireAjaxRequest();
		$folderId = blx()->request->getQuery('folderId');

		// Conflict resolution data
		$userResponse = blx()->request->getPost('userResponse');
		$responseInfo = blx()->request->getPost('additionalInfo');
		$fileName = blx()->request->getPost('fileName');

		$response = blx()->assets->uploadFile($folderId, $userResponse, $responseInfo, $fileName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * View a folder
	 */
	public function actionViewFolder()
	{
		$this->requireAjaxRequest();
		$folderId = blx()->request->getRequiredPost('folderId');
		$requestId = blx()->request->getPost('requestId', 0);
		$viewType = blx()->request->getPost('viewType', 'thumbs');
		$offset = blx()->request->getPost('offset', 0);

		$folder = blx()->assets->getFolderById($folderId);
		$files = blx()->assets->getFilesByFolderId($folderId, $offset);



		$subfolders = blx()->assets->findFolders(array(
			'parentId' => $folderId
		));

		$html = blx()->templates->render('assets/_views/folder_contents',
			array(
				'folder' => $folder,
				'subfolders' => $subfolders,
				'view' => $viewType,
				'files' => $files
			)
		);

		$this->returnJson(array(
			'requestId' => $requestId,
			'html' => $html,
			'total' => count($files)
		));
	}

	/**
	 * View a file's content.
	 */
	public function actionViewFile()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$requestId = blx()->request->getPost('requestId', 0);
		$fileId = blx()->request->getRequiredPost('fileId');
		$file = blx()->assets->getFileById($fileId);

		if (!$file)
		{
			throw new Exception(Blocks::t('No asset exists with the ID “{id}”.', array('id' => $fileId)));
		}

		$html = blx()->templates->render('assets/_views/file', array(
			'file' => $file
		));

		$this->returnJson(array(
			'requestId' => $requestId,
			'headHtml' => blx()->templates->getHeadHtml(),
			'bodyHtml' => $html,
			'footHtml' => blx()->templates->getFootHtml(),
		));
	}

	/**
	 * Save a file's content.
	 */
	public function actionSaveFileContent()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$fileId = blx()->request->getRequiredPost('fileId');
		$file = blx()->assets->getFileById($fileId);

		if (!$file)
		{
			throw new Exception(Blocks::t('No asset exists with the ID “{id}”.', array('id' => $fileId)));
		}

		$fields = blx()->request->getPost('fields', array());
		$file->setContent($fields);

		$success = blx()->assets->saveFileContent($file);
		$this->returnJson(array('success' => $success));
	}

	/**
	 * Create a folder.
	 */
	public function actionCreateFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$parentId = blx()->request->getRequiredPost('parentId');
		$folderName = blx()->request->getRequiredPost('folderName');

		$response = blx()->assets->createFolder($parentId, $folderName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Delete a folder.
	 */
	public function actionDeleteFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$folderId = blx()->request->getRequiredPost('folderId');
		$response = blx()->assets->deleteFolder($folderId);

		$this->returnJson($response->getResponseData());

	}
}
