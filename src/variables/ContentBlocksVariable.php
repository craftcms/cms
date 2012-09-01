<?php
namespace Blocks;

/**
 * Content block functions
 */
class ContentBlocksVariable
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
	 * Gets a content block by its class handle.
	 *
	 * @param string $class
	 * @return BaseBlock
	 */
	public function getBlockByClass($class)
	{
		return blx()->blocks->getBlockByClass($class);
	}
}
