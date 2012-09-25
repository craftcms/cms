<?php
namespace Blocks;

/**
 *
 */
class BlockTypesService extends BaseApplicationComponent
{
	/**
	 * Returns all installed blocktypes.
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
	 * @param BaseBlockPackage $blockPackage
	 * @return BaseBlock|null
	 */
	public function populateBlockType(BaseBlockPackage $blockPackage)
	{
		return blx()->components->populateComponentByTypeAndPackage('block', $blockPackage);
	}
}
