<?php
namespace Craft;

/**
 * Handles asset indexing and sizing tasks
 */
class AssetOperationsController extends BaseController
{
	/**
	 * Get an indexing session ID
	 */
	public function actionGetSessionId()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$this->returnJson(array('sessionId' => craft()->assetIndexing->getIndexingSessionId()));
	}

	/**
	 * Start indexing.
	 */
	public function actionStartIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sourceId = craft()->request->getRequiredPost('sourceId');
		$sessionId = craft()->request->getRequiredPost('session');

		if (craft()->request->getPost('doIndexes'))
		{
			// We have to do the indexing - get the actual list from the disk
			$this->returnJson(craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId));
		}
		else
		{
			// Just the transformations, so get the indexed file list.
			$this->returnJson(array(
				'sourceId' => 	$sourceId,
				'total' => craft()->assets->getTotalFiles(array('sourceId' => $sourceId))
			));
		}
	}

	/**
	 * Do the indexing.
	 */
	public function actionPerformIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sourceId = craft()->request->getRequiredPost('sourceId');
		$sessionId = craft()->request->getRequiredPost('session');
		$offset = craft()->request->getRequiredPost('offset');

		if (craft()->request->getPost('doIndexes'))
		{
			$fileId = craft()->assetIndexing->processIndexForSource($sessionId, $offset, $sourceId);
			$return = array('success' => (bool) $fileId);
		}

		// Do the transformation update
		$transformationsToUpdate = craft()->request->getPost('doTransformations');
		if ($transformationsToUpdate)
		{
			// Did indexing already fill this one for us?
			if (empty($fileId))
			{
				// Okay, let's get the file from the file list, then.
				$file = craft()->assets->findFile(array('sourceId' => $sourceId , 'offset' => $offset));
			}
			else
			{
				$file = craft()->assets->getFileById($fileId);
			}

			if ($file instanceof AssetFileModel)
			{
				if (craft()->assetTransformations->updateTransformations($file, $transformationsToUpdate))
				{
					$return = array('success' => true);
				}
			}
		}

		if (empty($return))
		{
			$this->returnErrorJson(Craft::t("@@@appName@@@ couldn't find the requested file."));
		}
		else
		{
			$this->returnJson($return);
		}
	}

	/**
	 * Finish the indexing.
	 */
	public function actionFinishIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sources = craft()->request->getRequiredPost('sources');
		$command = craft()->request->getRequiredPost('command');
		$sessionId = craft()->request->getRequiredPost('sessionId');

		$sources = explode(",", $sources);

		$this->returnJson(craft()->assetIndexing->finishIndex($sessionId, $sources, $command));
	}
}
