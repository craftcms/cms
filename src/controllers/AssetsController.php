<?php
namespace Craft;

/**
 * Handles asset tasks
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
			'element'  => $file,
			'hasTitle' => true
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

		$fieldNamespace = craft()->request->getPost('fieldNamespace');
		$fields = craft()->request->getPost($fieldNamespace);
		$file->setContentFromPost($fields);

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
		$this->requireLogin();

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
		$this->requireLogin();

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

		$transformId = craft()->request->getPost('transformId');

		// If transform Id was not passed in, see if file id and handle were.
		if (empty($transformId))
		{
			$fileId = craft()->request->getPost('fileId');
			$handle = craft()->request->getPost('handle');
			$fileModel = craft()->assets->getFileById($fileId);
			$transformIndexModel = craft()->assetTransforms->getTransformIndex($fileModel, $handle);
			$transformId = $transformIndexModel->id;
		}
		else
		{
			$transformIndexModel = craft()->assetTransforms->getTransformIndexModelById($transformId);
		}

		if (!$transformIndexModel)
		{
			throw new Exception(Craft::t('No asset image transform exists with the ID “{id}”', array('id' => $transformId)));
		}

		// Make sure we're not in the middle of working on this transform from a separete request
		if ($transformIndexModel->inProgress)
		{
			// If it's been > 30 seconds, give up on that one
			$time = new DateTime();

			if ($time->getTimestamp() - $transformIndexModel->dateUpdated->getTimestamp() < 30)
			{
				echo 'working';
				craft()->end();
			}
		}

		if (!$transformIndexModel->fileExists)
		{
			$transformIndexModel->inProgress = 1;
			craft()->assetTransforms->storeTransformIndexData($transformIndexModel);

			$result = craft()->assetTransforms->generateTransform($transformIndexModel);

			if ($result)
			{
				$transformIndexModel->inProgress = 0;
				$transformIndexModel->fileExists = 1;
				craft()->assetTransforms->storeTransformIndexData($transformIndexModel);
				echo 'success:'.craft()->assetTransforms->getUrlforTransformByIndexId($transformId);
				craft()->end();
			}
			else
			{
				// No source file. Throw a 404.
				$transformIndexModel->inProgress = 0;
				craft()->assetTransforms->storeTransformIndexData($transformIndexModel);
				throw new HttpException(404, Craft::t("The requested image could not be found!"));
			}

		}

		echo 'success:'.craft()->assetTransforms->getUrlforTransformByIndexId($transformId);
		craft()->end();
	}

	/**
	 * Get information about available transforms.
	 */
	public function actionGetTransformInfo()
	{
		$this->requireAjaxRequest();
		$transforms = craft()->assetTransforms->getAllTransforms();
		$output = array();
		foreach ($transforms as $handle => $transform)
		{
			$output[] = (object) array('id' => $transform->id, 'handle' => $handle, 'name' => $transform->name);
		}

		$this->returnJson($output);
	}
}

