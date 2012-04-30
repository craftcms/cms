<?php
namespace Blocks;

/**
 * Content block functions
 */
class ContentBlocksVariable
{
	/**
	 * Returns all blocktypes
	 */
	public function blocktypes()
	{
		return b()->blocks->getBlockTypes();
	}
}
