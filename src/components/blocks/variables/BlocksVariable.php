<?php
namespace Blocks;

/**
 * Block functions
 */
class BlocksVariable
{
	/**
	 * Returns all installed block types.
	 *
	 * @return array
	 */
	public function getAllBlockTypes()
	{
		$blockTypes = blx()->blockTypes->getAllBlockTypes();
		return BlockTypeVariable::populateVariables($blockTypes);
	}

	/**
	 * Gets a block type.
	 *
	 * @param string $class
	 * @return BlockTypeVariable|null
	 */
	public function getBlockType($class)
	{
		$blockType = blx()->blockTypes->getBlockType($class);
		if ($blockType)
		{
			return new BlockTypeVariable($blockType);
		}
	}
}
