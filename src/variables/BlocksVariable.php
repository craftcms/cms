<?php
namespace Blocks;

/**
 * Block functions
 */
class BlocksVariable
{
	/**
	 * Returns all blocktypes.
	 *
	 * @return array
	 */
	public function blocktypes()
	{
		return blx()->blocks->getBlockTypes();
	}

	/**
	 * Gets a blocktype by its class.
	 *
	 * @param string $class
	 * @return BaseBlock
	 */
	public function getBlockByClass($class)
	{
		return blx()->blocks->getBlockByClass($class);
	}
}
