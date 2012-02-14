<?php
namespace Blocks;

/**
 *
 */
class ContentBlocksService extends BaseService
{
	private $_blockTypes;

	/**
	 * Returns all content blocks
	 * @return array
	 */
	public function getBlocks()
	{
		return ContentBlock::model()->findAll();
	}

	/**
	 * Returns a ContentBlock instance, whether it already exists based on an ID, or is new
	 * @param int $blockId The ContentBlock ID if it exists
	 * @return ContentBlock
	 */
	public function getBlock($blockId = null)
	{
		if ($blockId)
			$block = $this->getBlockById($blockId);

		if (empty($block))
			$block = new ContentBlock;

		return $block;
	}

	/**
	 * Returns a block by its ID
	 * @param int $blockId
	 * @return ContentBlock
	 */
	public function getBlockById($blockId)
	{
		return ContentBlock::model()->findById($blockId);
	}

	/** 
	 * Saves a block
	 */
	public function saveBlock($blockSettings, $blockTypeSettings = null, $blockId = null)
	{
		$block = $this->getBlock($blockId);

		$block->name = $blockSettings['name'];
		$block->handle = $blockSettings['handle'];
		$block->class = $blockSettings['class'];
		$block->instructions = $blockSettings['instructions'];
		$block->site_id = Blocks::app()->sites->currentSite->id;

		if ($block->validate())
		{
			$blockType = $this->getBlockType($block->class);

			if ($blockType->validateSettings($blockTypeSettings))
			{
				// Start a transaction
				$transaction = Blocks::app()->db->beginTransaction();
				try
				{
					// Delete the previous block type settings
					if (!$block->isNewRecord)
					{
						ContentBlockSetting::model()->deleteAllByAttributes(array(
							'block_id' => $block->id
						));
					}

					// Save the block
					$block->save();

					// Save the block type settings
					$blockTypeSettings = $blockType->onBeforeSaveSettings($blockTypeSettings);
					$flattened = ArrayHelper::flattenArray($blockTypeSettings);

					foreach ($flattened as $key => $value)
					{
						$setting = new ContentBlockSetting;
						$setting->block_id = $block->id;
						$setting->key = $key;
						$setting->value = $value;
						$setting->save();
					}

					$transaction->commit();
				}
				catch (Exception $e)
				{
					$transaction->rollBack();
					throw $e;
				}
			}
		}

		return $block;
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
					if ($className !== 'BaseBlock')
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
