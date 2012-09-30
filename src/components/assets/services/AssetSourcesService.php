<?php
namespace Blocks;

/**
 *
 */
class AssetSourcesService extends BaseApplicationComponent
{
	/**
	 * Returns all installed source types.
	 *
	 * @return array
	 */
	public function getAllSourceTypes()
	{
		return blx()->components->getComponentsByType('assetSource');
	}

	/**
	 * Gets an asset source type.
	 *
	 * @param string $class
	 * @return BaseAssetSource|null
	 */
	public function getSourceType($class)
	{
		return blx()->components->getComponentByTypeAndClass('assetSource', $class);
	}

	/**
	 * Populates an asset source type.
	 *
	 * @param AssetSourceModel $block
	 * @return BaseAssetSource|null
	 */
	public function populateSourceType(AssetSourceModel $source)
	{
		return blx()->components->populateComponentByTypeAndPackage('assetSource', $source);
	}

	/**
	 * Populates an asset source package.
	 *
	 * @param array|AssetSourceRecord $attributes
	 * @return AssetSourceModel
	 */
	public function populateSource($attributes)
	{
		if ($attributes instanceof AssetSourceRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$source = new AssetSourceModel();

		$source->id = $attributes['id'];
		$source->name = $attributes['name'];
		$source->type = $attributes['type'];
		$source->settings = $attributes['settings'];

		return $source;
	}

	/**
	 * Mass-populates asset source packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateSources($data, $index = 'id')
	{
		$sources = array();

		foreach ($data as $attributes)
		{
			$source = $this->populateSource($attributes);
			$sources[$source->$index] = $source;
		}

		return $sources;
	}

	/**
	 * Returns all asset sources.
	 *
	 * @return array
	 */
	public function getAllSources()
	{
		$sourceRecords = AssetSourceRecord::model()->ordered()->findAll();
		return $this->populateSources($sourceRecords);
	}

	/**
	 * Gets an asset source by its ID.
	 *
	 * @param int $sourceId
	 * @return AssetSourceModel
	 */
	public function getSourceById($sourceId)
	{
		$sourceRecord = AssetSourceRecord::model()->findById($sourceId);
		if ($sourceRecord)
		{
			return $this->populateSource($sourceRecord);
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

		$sourceType = blx()->assetSources->populateSourceType($source);

		$recordValidates = $sourceRecord->validate();
		$settingsValidate = $sourceType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the source type has had a chance to tweak them
			$sourceRecord->settings = $sourceType->getSettings()->getAttributes();

			$isNewSource = $sourceRecord->isNewRecord();
			if ($isNewSource)
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from('assetsources')
					->queryScalar();

				$sourceRecord->sortOrder = $maxSortOrder + 1;
			}

			$sourceRecord->save(false);

			// Now that we have a source ID, save it on the package
			if (!$source->id)
			{
				$source->id = $sourceRecord->id;
			}

			return true;
		}
		else
		{
			$source->addErrors($sourceRecord->getErrors());
			$source->addSettingErrors($sourceType->getSettings()->getErrors());

			return false;
		}
	}

	/**
	 * Reorders asset sources.
	 *
	 * @param array $sourceIds
	 * @return bool
	 */
	public function reorderSources($sourceIds)
	{
		$transaction = blx()->db->beginTransaction();

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
		$sourceRecord = $this->_getSourceRecordById($sourceId);
		$source = $this->populateSource($sourceRecord);

		$transaction = blx()->db->beginTransaction();
		try
		{
			$sourceRecord->delete();
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
				$this->_noSourceExists($sourceId);
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
		throw new Exception(Blocks::t('No source exists with the ID “{id}”', array('id' => $sourceId)));
	}
}
