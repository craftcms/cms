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
		return VariableHelper::populateComponentVariables($blocks, 'BlockVariable');
	}

	/**
	 * Gets a block by its class.
	 *
	 * @param string $class
	 * @return mixed
	 */
	public function getBlockByClass($class)
	{
		$block = blx()->blocks->getBlockByClass($class);
		if ($block)
			return new BlockVariable($block);
	}
}
