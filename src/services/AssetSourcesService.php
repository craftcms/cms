<?php
namespace Craft;

/**
 * Class AssetSourcesService
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.services
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
 */
class AssetSourcesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_allSourceIds;

	/**
	 * @var
	 */
	private $_viewableSourceIds;

	/**
	 * @var
	 */
	private $_viewableSources;

	/**
	 * @var
	 */
	private $_sourcesById;

	/**
	 * @var bool
	 */
	private $_fetchedAllSources = false;

	// Public Methods
	// =========================================================================

	// Source Types
	// -------------------------------------------------------------------------

	/**
	 * Returns all available source types.
	 *
	 * @return array
	 */
	public function getAllSourceTypes()
	{
		if (craft()->getEdition() == Craft::Pro)
		{
			return craft()->components->getComponentsByType(ComponentType::AssetSource);
		}
		else
		{
			return array(craft()->components->getComponentByTypeAndClass(ComponentType::AssetSource, 'Local'));
		}
	}

	/**
	 * Returns an asset source type by its class handle.
	 *
	 * @param string $class
	 *
	 * @return BaseAssetSourceType|null
	 */
	public function getSourceType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::AssetSource, $class);
	}

	/**
	 * Populates an asset source type with a given source.
	 *
	 * @param AssetSourceModel $source
	 *
	 * @return BaseAssetSourceType|null
	 */
	public function populateSourceType(AssetSourceModel $source)
	{
		return craft()->components->populateComponentByTypeAndModel(ComponentType::AssetSource, $source);
	}

	/**
	 * Returns a source type by a given source ID.
	 *
	 * @param $sourceId
	 *
	 * @return BaseAssetSourceType
	 */
	public function getSourceTypeById($sourceId)
	{
		$source = $this->getSourceById($sourceId);
		return $this->populateSourceType($source);
	}

	// Sources
	// -------------------------------------------------------------------------

	/**
	 * Returns all of the source IDs.
	 *
	 * @return array
	 */
	public function getAllSourceIds()
	{
		if (!isset($this->_allSourceIds))
		{
			if ($this->_fetchedAllSources)
			{
				$this->_allSourceIds = array_keys($this->_sourcesById);
			}
			else
			{
				$this->_allSourceIds = craft()->db->createCommand()
					->select('id')
					->from('assetsources')
					->queryColumn();
			}
		}

		return $this->_allSourceIds;
	}

	/**
	 * Returns all source IDs that are viewable by the current user.
	 *
	 * @return array
	 */
	public function getViewableSourceIds()
	{
		if (!isset($this->_viewableSourceIds))
		{
			$this->_viewableSourceIds = array();

			foreach ($this->getAllSourceIds() as $sourceId)
			{
				if (craft()->userSession->checkPermission('viewAssetSource:'.$sourceId))
				{
					$this->_viewableSourceIds[] = $sourceId;
				}
			}
		}

		return $this->_viewableSourceIds;
	}

	/**
	 * Returns all sources that are viewable by the current user.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getViewableSources($indexBy = null)
	{
		if (!isset($this->_viewableSources))
		{
			$this->_viewableSources = array();

			foreach ($this->getAllSources() as $source)
			{
				if (craft()->userSession->checkPermission('viewAssetSource:'.$source->id))
				{
					$this->_viewableSources[] = $source;
				}
			}
		}

		if (!$indexBy)
		{
			return $this->_viewableSources;
		}
		else
		{
			$sources = array();

			foreach ($this->_viewableSources as $source)
			{
				$sources[$source->$indexBy] = $source;
			}

			return $sources;
		}
	}

	/**
	 * Returns the total number of sources
	 *
	 * @return int
	 */
	public function getTotalSources()
	{
		return count($this->getAllSourceIds());
	}

	/**
	 * Returns the total number of sources that are viewable by the current user.
	 *
	 * @return int
	 */
	public function getTotalViewableSources()
	{
		return count($this->getViewableSourceIds());
	}

	/**
	 * Returns all sources.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllSources($indexBy = null)
	{
		if (!$this->_fetchedAllSources)
		{
			$this->_sourcesById = array();

			$results = $this->_createSourceQuery()->queryAll();

			foreach ($results as $result)
			{
				$source = $this->_populateSource($result);
				$this->_sourcesById[$source->id] = $source;
			}

			$this->_fetchedAllSources = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_sourcesById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_sourcesById);
		}
		else
		{
			$sources = array();

			foreach ($this->_sourcesById as $source)
			{
				$sources[$source->$indexBy] = $source;
			}

			return $sources;
		}
	}

	/**
	 * Returns a source by its ID.
	 *
	 * @param int $sourceId
	 *
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($sourceId)
	{
		// Temporary source?
		if (is_null($sourceId))
		{
			$source = new AssetSourceModel();
			$source->id = $sourceId;
			$source->name = TempAssetSourceType::sourceName;
			$source->type = TempAssetSourceType::sourceType;
			$source->settings = array('path' => craft()->path->getAssetsTempSourcePath(), 'url' => rtrim(UrlHelper::getResourceUrl(), '/').'/tempassets/');
			return $source;
		}
		else
		{
			// If we've already fetched all sources we can save ourselves a trip to the DB for source IDs that don't
			// exist
			if (!$this->_fetchedAllSources &&
				(!isset($this->_sourcesById) || !array_key_exists($sourceId, $this->_sourcesById))
			)
			{
				$result = $this->_createSourceQuery()
					->where('id = :id', array(':id' => $sourceId))
					->queryRow();

				if ($result)
				{
					$source = $this->_populateSource($result);
				}
				else
				{
					$source = null;
				}

				$this->_sourcesById[$sourceId] = $source;
			}

			if (!empty($this->_sourcesById[$sourceId]))
			{
				return $this->_sourcesById[$sourceId];
			}
		}
	}

	/**
	 * Saves an asset source.
	 *
	 * @param AssetSourceModel $source
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSource(AssetSourceModel $source)
	{
		$sourceRecord = $this->_getSourceRecordById($source->id);

		$isNewSource = $sourceRecord->isNewRecord();

		if (!$isNewSource)
		{
			$oldSource = AssetSourceModel::populateModel($sourceRecord);
		}

		$sourceRecord->name          = $source->name;
		$sourceRecord->handle        = $source->handle;
		$sourceRecord->type          = $source->type;
		$sourceRecord->fieldLayoutId = $source->fieldLayoutId;

		$sourceType = $this->populateSourceType($source);
		$processedSettings = $sourceType->prepSettings($source->settings);
		$sourceRecord->settings = $source->settings = $processedSettings;
		$sourceType->setSettings($processedSettings);

		$recordValidates = $sourceRecord->validate();
		$settingsValidate = $sourceType->getSettings()->validate();
		$sourceErrors = $sourceType->getSourceErrors();


		if ($recordValidates && $settingsValidate && empty($sourceErrors))
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				if ($isNewSource)
				{
					// Set the sort order
					$maxSortOrder = craft()->db->createCommand()
						->select('max(sortOrder)')
						->from('assetsources')
						->queryScalar();

					$sourceRecord->sortOrder = $maxSortOrder + 1;
				}

				if (!$isNewSource && $oldSource->fieldLayoutId)
				{
					// Drop the old field layout
					craft()->fields->deleteLayoutById($oldSource->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $source->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Update the source record/model with the new layout ID
				$source->fieldLayoutId = $fieldLayout->id;
				$sourceRecord->fieldLayoutId = $fieldLayout->id;

				// Save the source
				$sourceRecord->save(false);

				if ($isNewSource)
				{
					// Now that we have a source ID, save it on the model
					$source->id = $sourceRecord->id;
				}
				else
				{
					// Update the top folder's name with the source's new name
					$topFolder = craft()->assets->findFolder(array('sourceId' => $source->id, 'parentId' => ':empty:'));

					if ($topFolder->name != $source->name)
					{
						$topFolder->name = $source->name;
						craft()->assets->storeFolder($topFolder);
					}
				}

				craft()->assetIndexing->ensureTopFolder($source);

				if ($transaction !== null)
				{
					$transaction->commit();
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}

            if ($isNewSource && $this->_fetchedAllSources)
            {
                $this->_sourcesById[$source->id] = $source;
            }

            if (isset($this->_viewableSourceIds))
            {
                if (craft()->userSession->checkPermission('viewAssetSource:'.$source->id))
                {
                    $this->_viewableSourceIds[] = $source->id;
                }
            }

			return true;
		}
		else
		{
			$source->addErrors($sourceRecord->getErrors());
			$source->addSettingErrors($sourceType->getSettings()->getErrors());
			$source->addSettingErrors($sourceErrors);

			return false;
		}
	}

	/**
	 * Reorders asset sources.
	 *
	 * @param array $sourceIds
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderSources($sourceIds)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			foreach ($sourceIds as $sourceOrder => $sourceId)
			{
				$sourceRecord = $this->_getSourceRecordById($sourceId);
				$sourceRecord->sortOrder = $sourceOrder+1;
				$sourceRecord->save();
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Deletes an asset source by its ID.
	 *
	 * @param int $sourceId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteSourceById($sourceId)
	{
		if (!$sourceId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Grab the asset file ids so we can clean the elements table.
			$assetFileIds = craft()->db->createCommand()
				->select('id')
				->from('assetfiles')
				->where(array('sourceId' => $sourceId))
				->queryColumn();

			craft()->elements->deleteElementById($assetFileIds);

			// Nuke the asset source.
			$affectedRows = craft()->db->createCommand()->delete('assetsources', array('id' => $sourceId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object prepped for retrieving sources.
	 *
	 * @return DbCommand
	 */
	private function _createSourceQuery()
	{
		return craft()->db->createCommand()
			->select('id, fieldLayoutId, name, handle, type, settings, sortOrder')
			->from('assetsources')
			->order('sortOrder');
	}

	/**
	 * Populates a source from its DB result.
	 *
	 * @param array $result
	 *
	 * @return AssetSourceModel
	 */
	private function _populateSource($result)
	{
		if ($result['settings'])
		{
			$result['settings'] = JsonHelper::decode($result['settings']);
		}

		return new AssetSourceModel($result);
	}

	/**
	 * Gets a source's record.
	 *
	 * @param int $sourceId
	 *
	 * @throws Exception
	 * @return AssetSourceRecord
	 */
	private function _getSourceRecordById($sourceId = null)
	{
		if ($sourceId)
		{
			$sourceRecord = AssetSourceRecord::model()->findById($sourceId);

			if (!$sourceRecord)
			{
				throw new Exception(Craft::t('No source exists with the ID “{id}”.', array('id' => $sourceId)));
			}
		}
		else
		{
			$sourceRecord = new AssetSourceRecord();
		}

		return $sourceRecord;
	}
}
