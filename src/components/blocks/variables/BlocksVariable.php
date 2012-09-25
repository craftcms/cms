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
		return VariableHelper::populateVariables($blockTypes, 'BlockVariable');
	}

	/**
	 * Gets a block type.
	 *
	 * @param string $class
	 * @return BaseBlock|null
	 */
	public function getBlockType($class)
	{
		$blockType = blx()->blockTypes->getBlockType($class);
		if ($blockType)
		{
			return new BlockVariable($blockType);
		}
	}

	/**
	 * Populates a block type.
	 *
	 * @param BaseBlockPackage $block
	 * @return BaseBlock|null
	 */
	public function populateBlockType(BaseBlockPackage $block)
	{
		$blockType = blx()->blockTypes->populateBlockType($block);
		if ($blockType)
		{
			return new BlockVariable($blockType);
		}
	}
}
