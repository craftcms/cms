<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use craft\app\Craft;

/**
 * Asset Index tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetIndex extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Update Asset Indexes');
	}

	/**
	 * @inheritDoc ToolInterface::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'assets';
	}

	/**
	 * @inheritDoc ToolInterface::getOptionsHtml()
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
	 * @inheritDoc ToolInterface::performAction()
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

			craft()->getSession()->add('assetsSourcesBeingIndexed', $sourceIds);
			craft()->getSession()->add('assetsMissingFolders', $missingFolders);
			craft()->getSession()->add('assetsTotalSourcesToIndex', count($sourceIds));
			craft()->getSession()->add('assetsTotalSourcesIndexed', 0);

			return array(
				'batches' => $batches,
				'total'   => $grandTotal
			);
		}
		else if (!empty($params['process']))
		{
			// Index the file
			craft()->assetIndexing->processIndexForSource($params['sessionId'], $params['offset'], $params['sourceId']);

			// More files to index.
			if (++$params['offset'] < $params['total'])
			{
				return array(
					'success' => true
				);
			}
			else
			{
				// Increment the amount of sources indexed
				craft()->getSession()->add('assetsTotalSourcesIndexed', craft()->getSession()->get('assetsTotalSourcesIndexed', 0) + 1);

				// Is this the last source to finish up?
				if (craft()->getSession()->get('assetsTotalSourcesToIndex', 0) <= craft()->getSession()->get('assetsTotalSourcesIndexed', 0))
				{
					$sourceIds = craft()->getSession()->get('assetsSourcesBeingIndexed', array());
					$missingFiles = craft()->assetIndexing->getMissingFiles($sourceIds, $params['sessionId']);
					$missingFolders = craft()->getSession()->get('assetsMissingFolders', array());

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
			}
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
