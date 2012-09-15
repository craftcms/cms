<?php
namespace Blocks;

/**
 * Block functions
 */
class BlocksVariable
{
	/**
	 * Returns all installed blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		$blocks = blx()->blocks->getAllBlocks();
		return VariableHelper::populateVariables($blocks, 'BlockVariable');
	}

	/**
	 * Gets a block by its class.
	 *
	 * @param string $class
	 * @return BaseBlock|null
	 */
	public function getBlockByClass($class)
	{
		$block = blx()->blocks->getBlockByClass($class);
		if ($block)
		{
			return new BlockVariable($block);
		}
	}

	/**
	 * Populates a block.
	 *
	 * @param BaseBlockPackage $blockPackage
	 * @return BaseBlock|null
	 */
	public function populateBlock(BaseBlockPackage $blockPackage)
	{
		$block = blx()->blocks->populateBlock($blockPackage);
		if ($block)
		{
			return new BlockVariable($block);
		}
	}
}
