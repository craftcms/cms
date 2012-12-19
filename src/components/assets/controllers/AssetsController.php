<?php
namespace Blocks;

/**
 * Handles asset tasks
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
		$folderId = blx()->request->getRequiredQuery('folderId');
		$userResponse = blx()->request->getPost('userResponse');

		$output = blx()->assets->uploadFile($folderId, $userResponse);

		$this->returnJson(array('success' => true));
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


		$html = blx()->templates->render('assets/_views/folder_contents',
			array(
				'folder' => $folder,
				'view' => $viewType,
				'files' => $files
			)
		);

		$this->returnJson(array(
			'requestId' => $requestId,
			'html' => $html
		));
	}

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
			'html' => $html
		));
	}
}
