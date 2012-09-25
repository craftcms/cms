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
		$blockTypes = blx()->blocks->getAllBlockTypes();
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
		$blockType = blx()->blocks->getBlockType($class);
		if ($blockType)
		{
			return new BlockVariable($blockType);
		}
	}

	/**
	 * Populates a block type.
	 *
	 * @param BaseBlockPackage $blockPackage
	 * @return BaseBlock|null
	 */
	public function populateBlockType(BaseBlockPackage $blockPackage)
	{
		$blockType = blx()->blocks->populateBlockType($blockPackage);
		if ($blockType)
		{
			return new BlockVariable($blockType);
		}
	}
}
