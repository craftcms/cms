<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends BaseComponent
{
	private $_blockTypes;

	/**
	 * Returns all content blocks
	 * @return array
	 */
	public function getBlocks()
	{
		$blocks = b()->db->createCommand()->from('blocks')->queryAll();
		return Block::model()->populateRecords($blocks);
	}

	/**
	 * Returns a new block by the blocktype
	 * @param string $class The blocktype class, sans "Blocktype" suffix
	 * @return mixed The block instance
	 */
	public function getBlockByClass($class)
	{
		$class = __NAMESPACE__.'\\'.$class.'Blocktype';
		$block = new $class;
		return $block;
	}

	/**
	 * Returns a block by its ID
	 * @param int $blockId
	 * @return Block
	 */
	public function getBlockById($blockId)
	{
		$block = b()->db->createCommand()
			->where('id = :id', array(':id' => $blockId))
			->from('blocks')
			->queryRow();

		if ($block)
			return Block::model()->populateRecord($block);

		return null;
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
		$block = $this->getBlockByClass($blockSettings['class']);
		$isNewBlock = true;

		if ($blockId)
		{
			$originalBlock = $this->getBlockById($blockId);
			if ($originalBlock)
			{
				$isNewBlock = false;
				$block->isNewRecord = false;
				$block->id = $blockId;
				$block->setPrimaryKey($blockId);
				$oldColumnName = $this->getContentColumnNameForBlock($originalBlock);
			}
		}

		$block->name = $blockSettings['name'];
		$block->handle = $blockSettings['handle'];
		$block->class = $blockSettings['class'];
		$block->instructions = $blockSettings['instructions'];
		$block->site_id = b()->sites->currentSite->id;

		if ($block->validate())
		{
			// Start a transaction
			$transaction = b()->db->beginTransaction();
			try
			{
				// Save the block
				$block->save();

				// Save the settings
				$block->settings = $blockTypeSettings;

				// Add or modify the block's content column
				$columnName = $this->getContentColumnNameForBlock($block);
				$columnType = DatabaseHelper::generateColumnDefinition($block->columnType);

				if ($isNewBlock)
				{
					// Add the new column
					b()->db->createCommand()->addColumn('content', $columnName, $columnType);
				}
				else
				{
					// Rename the column if the block has a new handle
					if ($columnName != $oldColumnName)
						b()->db->createCommand()->renameColumn('content', $oldColumnName, $columnName);

					// Update the column's type
					b()->db->createCommand()->alterColumn('content', $columnName, $columnType);
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

			if (($files = @glob(b()->path->blockTypesPath."*Blocktype.php")) !== false)
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
}
