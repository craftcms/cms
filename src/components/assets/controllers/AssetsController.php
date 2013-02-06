<?php
namespace Blocks;

/**
 * Handles asset tasks
 * TODO: Permissions?
 */
class AssetsController extends BaseEntityController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return AssetsService
	 */
	protected function getService()
	{
		return blx()->assets;
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

		$folder = blx()->assets->getFolderById($folderId);
		$files = blx()->assets->getFilesByFolderId($folderId);


		$subfolders = blx()->assets->findFolders(
			new FolderCriteria(
				array(
					'parentId' => $folderId
				)
			)
		);

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
			'html' => $html
		));
	}

	/**
	 * View a file's block content.
	 */
	public function actionViewFile()
	{
		$requestId = blx()->request->getPost('requestId', 0);
		$fileId = blx()->request->getRequiredPost('fileId');

		$html = blx()->templates->render('assets/_views/file',
			array(
				'file' => blx()->assets->getFileById($fileId)
			)
		);

		$this->returnJson(array(
			'requestId' => $requestId,
			'headHtml' => blx()->templates->getHeadHtml(),
			'bodyHtml' => $html,
			'footHtml' => blx()->templates->getFootHtml(),
		));
	}

	/**
	 * Save a file's block content.
	 */
	public function actionSaveFile()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$file = blx()->assets->getFileById(blx()->request->getRequiredPost('fileId'));

		if ($file)
		{
			$file->setContent(blx()->request->getPost('blocks'));
			blx()->assets->storeFileBlocks($file);
			$this->returnJson(array('success' => true));
		}
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
}
