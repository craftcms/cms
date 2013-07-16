<?php
namespace Craft;

/**
 *
 */
class AssetSourcesService extends BaseApplicationComponent
{
	private $_allSourceId;
	private $_viewableSourceIds;
	private $_sourcesByIds;

	/**
	 * Returns all of the source IDs.
	 *
	 * @return array
	 */
	public function getAllSourceIds()
	{
		if (!isset($this->_allSourceId))
		{
			$this->_allSourceId = craft()->db->createCommand()
				->select('id')
				->from('assetsources')
				->queryColumn();
		}

		return $this->_allSourceId;
	}

	/**
	 * Returns all of the source IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getViewableSourceIds()
	{
		if (!isset($this->_viewableSourceIds))
		{
			$this->_viewableSourceIds = array();
			$allSourceIds = $this->getAllSourceIds();

			foreach ($allSourceIds as $sourceId)
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
	 * Gets all asset source that are viewable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getViewableSources($indexBy = null)
	{
		$viewableSourceIds = $this->getViewableSourceIds();
		$sources = $this->getAllSources('id');
		$viewableSources = array();

		foreach ($viewableSourceIds as $sourceId)
		{
			if (isset($sources[$sourceId]))
			{
				$source = $sources[$sourceId];

				if ($indexBy)
				{
					$viewableSources[$source->$indexBy] = $source;
				}
				else
				{
					$viewableSources[] = $source;
				}
			}
		}

		return $viewableSources;
	}

	/**
	 * Returns all installed source types.
	 *
	 * @return array
	 */
	public function getAllSourceTypes()
	{
		if (Craft::hasPackage(CraftPackage::Cloud))
		{
			return craft()->components->getComponentsByType(ComponentType::AssetSource);
		}
		else
		{
			return array(craft()->components->getComponentByTypeAndClass(ComponentType::AssetSource, 'Local'));
		}
	}

	/**
	 * Gets the total number of Asset sources
	 *
	 * @return int
	 */
	public function getTotalSources()
	{
		return count($this->getAllSourceIds());
	}

	/**
	 * Gets the total number of sources that are viewable by the current user.
	 *
	 * @return int
	 */
	public function getTotalViewableSources()
	{
		return count($this->getViewableSourceIds());
	}

	/**
	 * Gets an asset source type.
	 *
	 * @param string $class
	 * @return BaseAssetSourceType|null
	 */
	public function getSourceType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::AssetSource, $class);
	}

	/**
	 * Populates an asset source type.
	 *
	 * @param AssetSourceModel $source
	 * @return BaseAssetSourceType|null
	 */
	public function populateSourceType(AssetSourceModel $source)
	{
		return craft()->components->populateComponentByTypeAndModel(ComponentType::AssetSource, $source);
	}

	/**
	 * Returns all asset sources.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSources($indexBy = null)
	{
		if (!isset($this->_sourcesByIds))
		{
			$sourceRecords = AssetSourceRecord::model()->ordered()->findAll();
			$this->_sourcesByIds = AssetSourceModel::populateModels($sourceRecords, $indexBy);
		}

		if ($indexBy == 'id')
		{
			$sources = $this->_sourcesByIds;
		}
		else if (!$indexBy)
		{
			$sources = array_values($this->_sourcesByIds);
		}
		else
		{
			$sources = array();
			foreach ($this->_sourcesByIds as $source)
			{
				$sources[$source->$indexBy] = $source;
			}
		}

		return $sources;
	}

	/**
	 * Gets an asset source by its ID.
	 *
	 * @param int $sourceId
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($sourceId)
	{
		$sourceRecord = AssetSourceRecord::model()->findById($sourceId);

		if ($sourceRecord)
		{
			return AssetSourceModel::populateModel($sourceRecord);
		}
	}

	/**
	 * Returns a source type by source id
	 * @param $sourceId
	 * @return BaseAssetSourceType
	 */
	public function getSourceTypeById($sourceId)
	{
		$source = $this->getSourceById($sourceId);
		return $this->populateSourceType($source);

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
		$transaction = craft()->db->beginTransaction();

		try
		{
			foreach ($sourceIds as $sourceOrder => $sourceId)
			{
				$sourceRecord = $this->_getSourceRecordById($sourceId);
				$sourceRecord->sortOrder = $sourceOrder+1;
				$sourceRecord->save();
			}

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
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
		craft()->db->createCommand()->delete('assetsources', array('id' => $sourceId));
		return true;
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
