<?php
namespace Blocks;

/**
 * Handles asset indexing tasks
 * TODO: clean up after indexing
 */
class AssetIndexingController extends BaseController
{
	/**
	 * Get an indexing session ID
	 */
	public function actionGetSessionId()
	{
		$this->requireAjaxRequest();
		$this->returnJson(array('session_id' => blx()->assetIndexing->getIndexingSessionId()));
	}

	/**
	 * Start indexing
	 */
	public function actionStartIndex()
	{
		$sourceId = blx()->request->getRequiredPost('source_id');
		$sessionId = blx()->request->getRequiredPost('session');

		$this->returnJson(blx()->assetIndexing->getIndexListForSource($sessionId, $sourceId));

	}

	/**
	 * Start indexing
	 */
	public function actionPerformIndex()
	{
		$sourceId = blx()->request->getRequiredPost('source_id');
		$sessionId = blx()->request->getRequiredPost('session');
		$offset = blx()->request->getRequiredPost('offset');

		$this->returnJson(blx()->assetIndexing->processIndexForSource($sessionId, $offset, $sourceId));

	}
}
