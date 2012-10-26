<?php
namespace Blocks;

/**
 * Handles asset tasks
 */
class AssetsController extends BaseController
{
	/**
	 * Upload a file
	 */
	public function actionUploadFile()
	{
		$this->requireAjaxRequest();
		$folderId = blx()->request->getRequiredQuery('folder_id');
		$userResponse = blx()->request->getPost('user_response');

		$output = blx()->assets->uploadFile($folderId, $userResponse);

		$this->returnJson(array('success' => true));
	}

	/**
	 * View a folder
	 */
	public function actionViewFolder()
	{
		$this->requireAjaxRequest();
		$folderId = blx()->request->getRequiredPost('folder_id');
		$requestId = blx()->request->getPost('request_id', 0);
		$viewType = blx()->request->getPost('view_type', 'thumbs');

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
			'request_id' => $requestId,
			'html' => $html
		));
	}
}
