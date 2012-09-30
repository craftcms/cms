<?php
namespace Blocks;

/**
 *
 */
class BlockTypesService extends BaseApplicationComponent
{
	/**
	 * Returns all installed block types.
	 *
	 * @return array
	 */
	public function getAllBlockTypes()
	{
		return blx()->components->getComponentsByType('block');
	}

	/**
	 * Gets a block type.
	 *
	 * @param string $class
	 * @return BaseBlock|null
	 */
	public function getBlockType($class)
	{
		return blx()->components->getComponentByTypeAndClass('block', $class);
	}

	/**
	 * Populates a block type.
	 *
	 * @param BaseBlockModel $block
	 * @return BaseBlock|null
	 */
	public function populateBlockType(BaseBlockModel $block)
	{
		return blx()->components->populateComponentByTypeAndPackage('block', $block);
	}
}
