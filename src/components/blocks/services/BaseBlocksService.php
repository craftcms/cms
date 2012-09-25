<?php
namespace Blocks;

/**
 * Base blocks service class
 */
abstract class BaseBlocksService extends BaseApplicationComponent
{
	protected $blockPackageClass;
	protected $blockRecordClass;
	protected $contentRecordClass;
	protected $placeBlockColumnsAfter;

	/**
	 * Populates a block package.
	 *
	 * @param array|BaseBlockRecord $attributes
	 * @return BaseBlockPackage
	 */
	public function populateBlockPackage($attributes)
	{
		if ($attributes instanceof BaseBlockRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$class = __NAMESPACE__.'\\'.$this->blockPackageClass;
		$blockPackage = new $class();

		$blockPackage->id = $attributes['id'];
		$blockPackage->name = $attributes['name'];
		$blockPackage->handle = $attributes['handle'];
		$blockPackage->instructions = $attributes['instructions'];
		/* BLOCKSPRO ONLY */
		$blockRecord->required = $blockPackage->required;
		$blockRecord->translatable = $blockPackage->translatable;
		/* end BLOCKSPRO ONLY */
		$blockPackage->type = $attributes['type'];
		$blockPackage->settings = $attributes['settings'];

		return $blockPackage;
	}

	/**
	 * Mass-populates block packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateBlockPackages($data, $index = 'id')
	{
		$blockPackages = array();

		foreach ($data as $attributes)
		{
			$blockPackage = $this->populateBlockPackage($attributes);
			$blockPackages[$blockPackage->$index] = $blockPackage;
		}

		return $blockPackages;
	}

	/**
	 * Populates a block record from a package.
	 *
	 * @access protected
	 * @param BaseBlockPackage $blockPackage
	 * @return BaseBlockRecord $blockRecord;
	 */
	protected function populateBlockRecord(BaseBlockPackage $blockPackage)
	{
		$blockRecord = $this->_getBlockRecordById($blockPackage->id);

		if (!$blockRecord->isNewRecord())
		{
			$blockRecord->oldHandle = $blockRecord->handle;
		}

		$blockRecord->name = $blockPackage->name;
		$blockRecord->handle = $blockPackage->handle;
		$blockRecord->instructions = $blockPackage->instructions;
		/* BLOCKSPRO ONLY */
		$blockRecord->required = $blockPackage->required;
		$blockRecord->translatable = $blockPackage->translatable;
		/* end BLOCKSPRO ONLY */
		$blockRecord->type = $blockPackage->type;

		return $blockRecord;
	}

	/**
	 * Returns all blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		$class = __NAMESPACE__.'\\'.$this->blockRecordClass;
		$blockRecords = $class::model()->ordered()->findAll();
		return $this->populateBlockPackages($blockRecords);
	}

	/**
	 * Gets a block by its ID.
	 *
	 * @param int $blockId
	 * @return BaseBlockPackage
	 */
	public function getBlockById($blockId)
	{
		$class = __NAMESPACE__.'\\'.$this->blockRecordClass;
		$blockRecord = $class::model()->findById($blockId);
		if ($blockRecord)
		{
			return $this->populateBlockPackage($blockRecord);
		}
	}

	/**
	 * Gets a block record or creates a new one.
	 *
	 * @access private
	 * @param int $blockId
	 * @return BaseBlockRecord
	 */
	private function _getBlockRecordById($blockId = null)
	{
		$class = __NAMESPACE__.'\\'.$this->blockRecordClass;

		if ($blockId)
		{
			$blockRecord = $class::model()->findById($blockId);

			if (!$blockRecord)
			{
				$this->_noBlockExists($blockId);
			}
		}
		else
		{
			$blockRecord = new $class();
		}

		return $blockRecord;
	}

	/**
	 * Throws a "No block exists" exception.
	 *
	 * @access private
	 * @param int $blockId
	 * @throws Exception
	 */
	private function _noBlockExists($blockId)
	{
		throw new Exception(Blocks::t('No block exists with the ID “{id}”', array('id' => $blockId)));
	}

	/**
	 * Saves a block.
	 *
	 * @param BaseBlockPackage $blockPackage
	 * @throws \Exception
	 * @return bool
	 */
	public function saveBlock(BaseBlockPackage $blockPackage)
	{
		$blockRecord = $this->populateBlockRecord($blockPackage);
		$blockType = blx()->blockTypes->populateBlockType($blockPackage);

		$recordValidates = $blockRecord->validate();
		$settingsValidate = $blockType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the block has had a chance to tweak them
			$blockRecord->settings = $blockType->getSettings()->getAttributes();

			$isNewBlock = $blockRecord->isNewRecord();
			if ($isNewBlock)
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from($blockRecord->getTableName())
					->queryScalar();

				$blockRecord->sortOrder = $maxSortOrder + 1;
			}

			$transaction = blx()->db->beginTransaction();
			try
			{
				$blockRecord->save(false);

				// Now that we have a block ID, save it on the package
				if (!$blockPackage->id)
				{
					$blockPackage->id = $blockRecord->id;
				}

				// Create/alter the content table column
				$contentTable = $this->getContentTable($blockPackage);
				$column = ModelHelper::normalizeAttributeConfig($blockType->defineContentAttribute());

				if ($isNewBlock)
				{
					blx()->db->createCommand()->addColumn($contentTable, $blockRecord->handle, $column);
				}
				else
				{
					blx()->db->createCommand()->alterColumn($contentTable, $blockRecord->oldHandle, $column, $blockRecord->handle);
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
		else
		{
			$blockPackage->errors = $blockRecord->getErrors();
			$blockPackage->settingsErrors = $blockType->getSettings()->getErrors();

			return false;
		}
	}

	/**
	 * Returns the content table name.
	 *
	 * @param BaseBlockPackage $blockPackage
	 * @access protected
	 * @return string
	 */
	protected function getContentTable(BaseBlockPackage $blockPackage)
	{
		$class = __NAMESPACE__.'\\'.$this->contentRecordClass;
		$contentRecord = new $class();
		return $contentRecord->getTableName();
	}

	/**
	 * Deletes a block by its ID.
	 *
	 * @param int $blockId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteBlockById($blockId)
	{
		$blockRecord = $this->_getBlockRecordById($blockId);
		$blockPackage = $this->populateBlockPackage($blockRecord);
		$contentTable = $this->getContentTable($blockPackage);

		$transaction = blx()->db->beginTransaction();
		try
		{
			$blockRecord->delete();
			blx()->db->createCommand()->dropColumn($contentTable, $blockRecord->handle);
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
	 * Reorders blocks.
	 *
	 * @param array $blockIds
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderBlocks($blockIds)
	{
		$lastColumn = $this->placeBlockColumnsAfter;

		$transaction = blx()->db->beginTransaction();
		try
		{
			foreach ($blockIds as $blockOrder => $blockId)
			{
				// Update the sortOrder in the blocks table
				$blockRecord = $this->_getBlockRecordById($blockId);
				$blockRecord->sortOrder = $blockOrder+1;
				$blockRecord->save();

				// Update the column order in the content table
				$blockPackage = $this->populateBlockPackage($blockRecord);
				$contentTable = $this->getContentTable($blockPackage);

				$blockType = blx()->blockTypes->populateBlockType($blockPackage);
				$column = ModelHelper::normalizeAttributeConfig($blockType->defineContentAttribute());

				blx()->db->createCommand()->alterColumn($contentTable, $blockRecord->handle, $column, null, $lastColumn);

				$lastColumn = $blockRecord->handle;
			}

			// Commit the transaction
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}
}
