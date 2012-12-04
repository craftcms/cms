<?php
namespace Blocks;

/**
 * Handles asset indexing tasks
 */
class AssetIndexingController extends BaseController
{
	/**
	 * Get an indexing session ID
	 */
	public function actionGetSessionId()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$this->returnJson(array('sessionId' => blx()->assetIndexing->getIndexingSessionId()));
	}

	/**
	 * Start indexing
	 */
	public function actionStartIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sourceId = blx()->request->getRequiredPost('sourceId');
		$sessionId = blx()->request->getRequiredPost('session');

		$this->returnJson(blx()->assetIndexing->getIndexListForSource($sessionId, $sourceId));
	}

	/**
	 * Start indexing
	 */
	public function actionPerformIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sourceId = blx()->request->getRequiredPost('sourceId');
		$sessionId = blx()->request->getRequiredPost('session');
		$offset = blx()->request->getRequiredPost('offset');

		$this->returnJson(blx()->assetIndexing->processIndexForSource($sessionId, $offset, $sourceId));

	}

	public function actionFinishIndex()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$sources = blx()->request->getRequiredPost('sources');
		$command = blx()->request->getRequiredPost('command');
		$sessionId = blx()->request->getRequiredPost('sessionId');

		$sources = explode(",", $sources);

		$this->returnJson(blx()->assetIndexing->finishIndex($sessionId, $sources, $command));
	}
}
