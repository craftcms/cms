<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends BaseService
{
	private $_blockTypes;

	/**
	 * Returns all content blocks
	 * @return array
	 */
	public function getBlocks()
	{
		return Block::model()->findAll(array(
			'order' => 'name'
		));
	}

	/**
	 * Returns a Block instance, whether it already exists based on an ID, or is new
	 * @param int $blockId The Block ID if it exists
	 * @return Block
	 */
	public function getBlock($blockId = null)
	{
		if ($blockId)
			$block = $this->getBlockById($blockId);

		if (empty($block))
			$block = new Block;

		return $block;
	}

	/**
	 * Returns a block by its ID
	 * @param int $blockId
	 * @return Block
	 */
	public function getBlockById($blockId)
	{
		return Block::model()->findById($blockId);
	}

	/**
	 * Saves a block
	 *
	 * @param      $blockSettings
	 * @param null $blockTypeSettings
	 * @param null $blockId
	 * @return \Blocks\Block
	 */
	public function saveBlock($blockSettings, $blockTypeSettings = null, $blockId = null)
	{
		$block = $this->getBlock($blockId);
		$isNewBlock = $block->isNewRecord;

		// Remember the original handle for later
		if (!$isNewBlock)
			$oldColumnName = $this->getContentColumnNameForBlock($block);

		$block->name = $blockSettings['name'];
		$block->handle = $blockSettings['handle'];
		$block->class = $blockSettings['class'];
		$block->instructions = $blockSettings['instructions'];
		$block->site_id = Blocks::app()->sites->currentSite->id;

		$blockType = $this->getBlockType($block->class);
		$blockType->settings = $blockTypeSettings;
		$block->blockType = $blockType;

		if ($block->validate())
		{
			// Start a transaction
			$transaction = Blocks::app()->db->beginTransaction();
			try
			{
				// Delete the previous block type settings
				if (!$isNewBlock)
				{
					BlockSetting::model()->deleteAllByAttributes(array(
						'block_id' => $block->id
					));
				}

				// Save the block
				$block->save();

				// Save the block type settings
				$blockType->onBeforeSaveSettings();
				$flattened = ArrayHelper::flattenArray($blockType->settings);
				foreach ($flattened as $key => $value)
				{
					$setting = new BlockSetting;
					$setting->block_id = $block->id;
					$setting->name = $key;
					$setting->value = $value;
					$setting->save();
				}

				// Add or modify the block's content column
				$columnName = $this->getContentColumnNameForBlock($block);
				$columnType = DatabaseHelper::generateColumnDefinition($blockType->columnType);

				if ($isNewBlock)
				{
					// Add the new column
					Blocks::app()->db->createCommand()->addColumn('{{content}}', $columnName, $columnType);
				}
				else
				{
					// Rename the column if the block has a new handle
					if ($columnName != $oldColumnName)
						Blocks::app()->db->createCommand()->renameColumn('{{content}}', $oldColumnName, $columnName);

					// Update the column's type
					Blocks::app()->db->createCommand()->alterColumn('{{content}}', $columnName, $columnType);
				}

				$transaction->commit();
			}
			catch (Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}
		}

		return $block;
	}

	public function getContentColumnNameForBlock($block)
	{
		return strtolower($block->handle).'_'.$block->id;
	}

	/**
	 * Returns all block types
	 * @return array
	 */
	public function getBlockTypes()
	{
		if (!isset($this->_blockTypes))
		{
			$this->_blockTypes = array();

			if (($files = @glob(Blocks::app()->path->blockTypesPath."*Block.php")) !== false)
			{
				foreach ($files as $file)
				{
					$className = pathinfo($file, PATHINFO_FILENAME);
					if (substr($className, 0, 4) !== 'Base')
					{
						$className = __NAMESPACE__.'\\'.$className;
						$this->_blockTypes[] = new $className;
					}
				}
			}
		}

		return $this->_blockTypes;
	}

	/**
	 * Returns a block type
	 * @param string $class The block type class
	 */
	public function getBlockType($class)
	{
		$className = __NAMESPACE__.'\\'.$class.'Block';
		return new $className;
	}

}
