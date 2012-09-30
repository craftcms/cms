<?php
namespace Blocks;

/**
 * Base blocks service class
 */
abstract class BaseBlocksService extends BaseApplicationComponent
{
	protected $blockModelClass;
	protected $blockRecordClass;
	protected $contentRecordClass;
	protected $placeBlockColumnsAfter;

	/**
	 * Populates a block model.
	 *
	 * @param array|BaseBlockRecord $attributes
	 * @return BaseBlockModel
	 */
	public function populateBlock($attributes)
	{
		if ($attributes instanceof BaseBlockRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$class = __NAMESPACE__.'\\'.$this->blockModelClass;
		$block = new $class();

		$block->id = $attributes['id'];
		$block->name = $attributes['name'];
		$block->handle = $attributes['handle'];
		$block->instructions = $attributes['instructions'];
		$block->required = $attributes['required'];
		$block->type = $attributes['type'];
		$block->settings = $attributes['settings'];

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$block->translatable = $attributes['translatable'];
		}

		return $block;
	}

	/**
	 * Mass-populates block packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateBlocks($data, $index = 'id')
	{
		$blocks = array();

		foreach ($data as $attributes)
		{
			$block = $this->populateBlock($attributes);
			$blocks[$block->$index] = $block;
		}

		return $blocks;
	}

	/**
	 * Populates a block record from a model.
	 *
	 * @access protected
	 * @param BaseBlockModel $block
	 * @return BaseBlockRecord $blockRecord;
	 */
	protected function populateBlockRecord(BaseBlockModel $block)
	{
		$blockRecord = $this->_getBlockRecordById($block->id);

		if (!$blockRecord->isNewRecord())
		{
			$blockRecord->oldHandle = $blockRecord->handle;
		}

		$blockRecord->name = $block->name;
		$blockRecord->handle = $block->handle;
		$blockRecord->instructions = $block->instructions;
		$blockRecord->required = $block->required;
		$blockRecord->type = $block->type;

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$blockRecord->translatable = $block->translatable;
		}

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
		return $this->populateBlocks($blockRecords);
	}

	/**
	 * Gets a block by its ID.
	 *
	 * @param int $blockId
	 * @return BaseBlockModel|null
	 */
	public function getBlockById($blockId)
	{
		$class = __NAMESPACE__.'\\'.$this->blockRecordClass;
		$blockRecord = $class::model()->findById($blockId);
		if ($blockRecord)
		{
			return $this->populateBlock($blockRecord);
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
	 * @param BaseBlockModel $block
	 * @throws \Exception
	 * @return bool
	 */
	public function saveBlock(BaseBlockModel $block)
	{
		$blockRecord = $this->populateBlockRecord($block);
		$blockType = blx()->blockTypes->populateBlockType($block);

		$recordValidates = $blockRecord->validate();
		$settingsValidate = $blockType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the block type has had a chance to tweak them
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

				// Now that we have a block ID, save it on the model
				if (!$block->id)
				{
					$block->id = $blockRecord->id;
				}

				// Create/alter the content table column
				$contentTable = $this->getContentTable($block);
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
			$block->addErrors($blockRecord->getErrors());
			$block->addSettingErrors($blockType->getSettings()->getErrors());
			return false;
		}
	}

	/**
	 * Returns the content table name.
	 *
	 * @param BaseBlockModel $block
	 * @access protected
	 * @return string
	 */
	protected function getContentTable(BaseBlockModel $block)
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
		$block = $this->populateBlock($blockRecord);
		$contentTable = $this->getContentTable($block);

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
				$block = $this->populateBlock($blockRecord);
				$contentTable = $this->getContentTable($block);

				$blockType = blx()->blockTypes->populateBlockType($block);
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
