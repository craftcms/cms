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
	public function getBlocks()
	{
		return blx()->blocks->getAllBlocks();
	}

	/**
	 * Gets a block by its class.
	 *
	 * @param string $class
	 * @return mixed
	 */
	public function getBlockByClass($class)
	{
		return blx()->blocks->getBlockByClass($class);
	}
}
