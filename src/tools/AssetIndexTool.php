<?php
namespace Craft;

/**
 * Asset Index tool.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.tools
 * @since     1.0
 */
class AssetIndexTool extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Update Asset Indexes');
	}

	/**
	 * @inheritDoc ITool::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'assets';
	}

	/**
	 * @inheritDoc ITool::getOptionsHtml()
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		$sources = craft()->assetSources->getAllSources();
		$sourceOptions = array();

		foreach ($sources as $source)
		{
			$sourceOptions[] = array(
				'label' => $source->name,
				'value' => $source->id
			);
		}

		return craft()->templates->render('_includes/forms/checkboxSelect', array(
			'name'    => 'sources',
			'options' => $sourceOptions
		));
	}

	/**
	 * @inheritDoc ITool::performAction()
	 *
	 * @param array $params
	 *
	 * @return array|null
	 */
	public function performAction($params = array())
	{
		// Initial request
		if (!empty($params['start']))
		{
			$batches = array();
			$sessionId = craft()->assetIndexing->getIndexingSessionId();

			// Selection of sources or all sources?
			if (is_array($params['sources']))
			{
				$sourceIds = $params['sources'];
			}
			else
			{
				$sourceIds = craft()->assetSources->getViewableSourceIds();
			}

			$missingFolders = array();
			$grandTotal = 0;

			foreach ($sourceIds as $sourceId)
			{
				// Get the indexing list
				$indexList = craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId);

				if (!empty($indexList['error']))
				{
					return $indexList;
				}

				if (isset($indexList['missingFolders']))
				{
					$missingFolders += $indexList['missingFolders'];
				}

				$batch = array();

				for ($i = 0; $i < $indexList['total']; $i++)
				{
					$batch[] = array(
						'params' => array(
							'sessionId' => $sessionId,
							'sourceId' => $sourceId,
							'total' => $indexList['total'],
							'offset' => $i,
							'process' => 1
						)
					);
				}

				$batches[] = $batch;
			}

			$batches[] = array(
				array(
					'params' => array(
						'overview' => true,
						'sessionId' => $sessionId,
					)
				)
			);

			craft()->httpSession->add('assetsSourcesBeingIndexed', $sourceIds);
			craft()->httpSession->add('assetsMissingFolders', $missingFolders);

			return array(
				'batches' => $batches,
				'total'   => $grandTotal
			);
		}
		else if (!empty($params['process']))
		{
			// Index the file
			craft()->assetIndexing->processIndexForSource($params['sessionId'], $params['offset'], $params['sourceId']);

			return array(
				'success' => true
			);
		}
		else if (!empty($params['overview']))
		{
			$sourceIds = craft()->httpSession->get('assetsSourcesBeingIndexed', array());
			$missingFiles = craft()->assetIndexing->getMissingFiles($sourceIds, $params['sessionId']);
			$missingFolders = craft()->httpSession->get('assetsMissingFolders', array());

			$responseArray = array();

			if (!empty($missingFiles) || !empty($missingFolders))
			{
				$responseArray['confirm'] = craft()->templates->render('assets/_missing_items', array('missingFiles' => $missingFiles, 'missingFolders' => $missingFolders));
				$responseArray['params'] = array('finish' => 1);
			}
			// Clean up stale indexing data (all sessions that have all recordIds set)
			$sessionsInProgress = craft()->db->createCommand()
					->select('sessionId')
					->from('assetindexdata')
					->where('recordId IS NULL')
					->group('sessionId')
					->queryScalar();

			if (empty($sessionsInProgress))
			{
				craft()->db->createCommand()->delete('assetindexdata');
			}
			else
			{
				craft()->db->createCommand()->delete('assetindexdata', array('not in', 'sessionId', $sessionsInProgress));
			}


			// Generate the HTML for missing files and folders
			return array(
				'batches' => array(
					array(
						$responseArray
					)
				)
			);
		}
		else if (!empty($params['finish']))
		{
			if (!empty($params['deleteFile']) && is_array($params['deleteFile']))
			{
				craft()->assetIndexing->removeObsoleteFileRecords($params['deleteFile']);
			}

			if (!empty($params['deleteFolder']) && is_array($params['deleteFolder']))
			{
				craft()->assetIndexing->removeObsoleteFolderRecords($params['deleteFolder']);
			}

			return array(
				'finished' => 1
			);
		}

		return array();
	}
}
