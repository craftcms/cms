<?php
namespace Blocks;

/**
 * Block type functions
 */
class BlockTypesVariable
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

	/**
	 * Populates a block type.
	 *
	 * @param BaseBlockModel $block
	 * @param BaseModel|null $entity
	 * @return BaseBlockType|null
	 */
	public function populateBlockType(BaseBlockModel $block, $entity = null)
	{
		$blockType = blx()->blockTypes->populateBlockType($block);
		if ($blockType)
		{
			$blockType->entity = $entity;
			return new BlockTypeVariable($blockType);
		}
	}
}
