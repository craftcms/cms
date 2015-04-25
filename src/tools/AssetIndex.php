<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use Craft;
use craft\app\base\Tool;
use craft\app\db\Query;

/**
 * AssetIndex represents an Update Asset Indexes tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetIndex extends Tool
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Update Asset Indexes');
	}

	/**
	 * @inheritdoc
	 */
	public static function iconValue()
	{
		return 'assets';
	}

	/**
	 * @inheritdoc
	 */
	public static function optionsHtml()
	{
		$sources = Craft::$app->getVolumes()->getAllVolumes();
		$sourceOptions = [];

		foreach ($sources as $source)
		{
			$sourceOptions[] = [
				'label' => $source->name,
				'value' => $source->id
			];
		}

		return Craft::$app->getView()->renderTemplate('_includes/forms/checkboxSelect', [
			'name'    => 'sources',
			'options' => $sourceOptions
		]);
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function performAction($params = [])
	{
		// Initial request
		if (!empty($params['start']))
		{
			$batches = [];
			$sessionId = Craft::$app->getAssetIndexer()->getIndexingSessionId();

			// Selection of sources or all sources?
			if (is_array($params['sources']))
			{
				$sourceIds = $params['sources'];
			}
			else
			{
				$sourceIds = Craft::$app->getVolumes()->getViewableVolumeIds();
			}

			$missingFolders = [];
			$skippedFiles = [];

			$grandTotal = 0;

			foreach ($sourceIds as $sourceId)
			{
				// Get the indexing list
				$indexList = Craft::$app->getAssetIndexer()->prepareIndexList($sessionId, $sourceId);

				if (!empty($indexList['error']))
				{
					return $indexList;
				}

				if (isset($indexList['missingFolders']))
				{
					$missingFolders += $indexList['missingFolders'];
				}

				if (isset($indexList['skippedFiles']))
				{
					$skippedFiles = $indexList['skippedFiles'];
				}

				$batch = [];

				for ($i = 0; $i < $indexList['total']; $i++)
				{
					$batch[] = [
								'params' => [
									'sessionId' => $sessionId,
									'sourceId' => $sourceId,
									'total' => $indexList['total'],
									'offset' => $i,
									'process' => 1
								]
					];
				}

				$batches[] = $batch;
			}

			Craft::$app->getSession()->set('assetsSourcesBeingIndexed', $sourceIds);
			Craft::$app->getSession()->set('assetsMissingFolders', $missingFolders);
			Craft::$app->getSession()->set('assetsTotalSourcesToIndex', count($sourceIds));
			Craft::$app->getSession()->set('assetsSkippedFiles', $skippedFiles);
			Craft::$app->getSession()->set('assetsTotalSourcesIndexed', 0);

			return [
				'batches' => $batches,
				'total'   => $grandTotal
			];
		}
		else if (!empty($params['process']))
		{
			// Index the file
			Craft::$app->getAssetIndexer()->processIndexForVolume($params['sessionId'], $params['offset'], $params['sourceId']);

			// More files to index.
			if (++$params['offset'] < $params['total'])
			{
				return [
					'success' => true
				];
			}
			else
			{
				// Increment the amount of sources indexed
				Craft::$app->getSession()->set('assetsTotalSourcesIndexed', Craft::$app->getSession()->get('assetsTotalSourcesIndexed', 0) + 1);

				// Is this the last source to finish up?
				if (Craft::$app->getSession()->get('assetsTotalSourcesToIndex', 0) <= Craft::$app->getSession()->get('assetsTotalSourcesIndexed', 0))
				{
					$sourceIds = Craft::$app->getSession()->get('assetsSourcesBeingIndexed', []);
					$missingFiles = Craft::$app->getAssetIndexer()->getMissingFiles($sourceIds, $params['sessionId']);
					$missingFolders = Craft::$app->getSession()->get('assetsMissingFolders', []);
					$skippedFiles = Craft::$app->getSession()->get('assetsSkippedFiles', []);

					$responseArray = [];

					if (!empty($missingFiles) || !empty($missingFolders) || !empty($skippedFiles))
					{
						$responseArray['confirm'] = Craft::$app->getView()->renderTemplate('assets/_missing_items', ['missingFiles' => $missingFiles, 'missingFolders' => $missingFolders, 'skippedFiles' => $skippedFiles]);
						$responseArray['params'] = ['finish' => 1];
					}
					// Clean up stale indexing data (all sessions that have all recordIds set)
					$sessionsInProgress = (new Query())
											->select('sessionId')
											->from('{{%assetindexdata}}')
											->where('recordId IS NULL')
											->groupBy('sessionId')
											->scalar();

					if (empty($sessionsInProgress))
					{
						Craft::$app->getDb()->createCommand()->delete('{{%assetindexdata}}')->execute();
					}
					else
					{
						Craft::$app->getDb()->createCommand()->delete('{{%assetindexdata}}', ['not in', 'sessionId', $sessionsInProgress])->execute();
					}

					// Generate the HTML for missing files and folders
					return [
						'batches' => [
							[
								$responseArray
							]
						]
					];
				}
			}
		}
		else if (!empty($params['finish']))
		{
			if (!empty($params['deleteFile']) && is_array($params['deleteFile']))
			{
				Craft::$app->getDb()->createCommand()->delete('assettransformindex', array('in', 'fileId', $params['deleteFile']));
				Craft::$app->getAssets()->deleteFilesByIds($params['deleteFile'], false);
			}

			if (!empty($params['deleteFolder']) && is_array($params['deleteFolder']))
			{
				Craft::$app->getAssets()->deleteFoldersByIds($params['deleteFolder'], false);
			}

			return [
				'finished' => 1
			];
		}

		return [];
	}
}
