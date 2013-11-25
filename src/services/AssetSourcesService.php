<?php
namespace Craft;

/**
 *
 */
class AssetSourcesService extends BaseApplicationComponent
{
	private $_allSourceIds;
	private $_viewableSourceIds;
	private $_viewableSources;
	private $_sourcesById;
	private $_fetchedAllSources = false;

	/* Source Types */

	/**
	 * Returns all available source types.
	 *
	 * @return array
	 */
	public function getAllSourceTypes()
	{
		if (craft()->hasPackage(CraftPackage::Cloud))
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
	 * @return BaseAssetSourceType
	 */
	public function getSourceTypeById($sourceId)
	{
		$source = $this->getSourceById($sourceId);
		return $this->populateSourceType($source);
	}

	/* Sources */

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
				$this->_allSourceIds = array_keys($this->getAllSources('id'));
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
	 * @return array
	 */
	public function getAllSources($indexBy = null)
	{
		if (!$this->_fetchedAllSources)
		{
			$sourceRecords = AssetSourceRecord::model()->ordered()->findAll();
			$this->_sourcesById = AssetSourceModel::populateModels($sourceRecords, 'id');
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
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($sourceId)
	{
		if (!$this->_fetchedAllSources && !isset($this->_sourcesById) || !array_key_exists($sourceId, $this->_sourcesById))
		{
			$sourceRecord = AssetSourceRecord::model()->findById($sourceId);

			if ($sourceRecord)
			{
				$this->_sourcesById[$sourceId] = AssetSourceModel::populateModel($sourceRecord);
			}
			else
			{
				$this->_sourcesById = null;
			}
		}

		if (!empty($this->_sourcesById[$sourceId]))
		{
			return $this->_sourcesById[$sourceId];
		}
	}

	/**
	 * Saves an asset source.
	 *
	 * @param AssetSourceModel $source
	 * @return bool
	 */
	public function saveSource(AssetSourceModel $source)
	{
		$sourceRecord = $this->_getSourceRecordById($source->id);
		$sourceRecord->name = $source->name;
		$sourceRecord->type = $source->type;

		$sourceType = $this->populateSourceType($source);
		$processedSettings = $sourceType->prepSettings($source->settings);
		$sourceRecord->settings = $source->settings = $processedSettings;
		$sourceType->setSettings($processedSettings);

		$recordValidates = $sourceRecord->validate();
		$settingsValidate = $sourceType->getSettings()->validate();
		$sourceErrors = $sourceType->getSourceErrors();


		if ($recordValidates && $settingsValidate && empty($sourceErrors))
		{
			$isNewSource = $sourceRecord->isNewRecord();

			if ($isNewSource)
			{
				$maxSortOrder = craft()->db->createCommand()
					->select('max(sortOrder)')
					->from('assetsources')
					->queryScalar();

				$sourceRecord->sortOrder = $maxSortOrder + 1;
			}
			else
			{
				$topFolder = craft()->assets->findFolder(array('sourceId' => $source->id, 'parentId' => FolderCriteriaModel::AssetsNoParent));
				if ($topFolder->name != $source->name)
				{
					$topFolder->name = $source->name;
					craft()->assets->storeFolder($topFolder);
				}
			}

			$sourceRecord->save(false);

			// Now that we have a source ID, save it on the model
			if (!$source->id)
			{
				$source->id = $sourceRecord->id;
			}

			craft()->assetIndexing->ensureTopFolder($source);
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

	/**
	 * Gets a source's record.
	 *
	 * @access private
	 * @param int $sourceId
	 * @return AssetSourceRecord
	 */
	private function _getSourceRecordById($sourceId = null)
	{
		if ($sourceId)
		{
			$sourceRecord = AssetSourceRecord::model()->findById($sourceId);

			if (!$sourceRecord)
			{
				$this->_noSourceExists($sourceId);
			}
		}
		else
		{
			$sourceRecord = new AssetSourceRecord();
		}

		return $sourceRecord;
	}

	/**
	 * Throws a "No source exists" exception.
	 *
	 * @access private
	 * @param int $sourceId
	 * @throws Exception
	 */
	private function _noSourceExists($sourceId)
	{
		throw new Exception(Craft::t('No source exists with the ID “{id}”', array('id' => $sourceId)));
	}
}
