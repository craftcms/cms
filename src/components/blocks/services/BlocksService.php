<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends BaseApplicationComponent
{
	/**
	 * Returns all installed blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->components->getComponentsByType('block');
	}

	/**
	 * Gets a block by its class
	 *
	 * @param string $class
	 * @return BaseBlock|null
	 */
	public function getBlockByClass($class)
	{
		return blx()->components->getComponentByTypeAndClass('block', $class);
	}

	/**
	 * Populates a block.
	 *
	 * @param BaseBlockPackage $blockPackage
	 * @return BaseBlock|null
	 */
	public function populateBlock(BaseBlockPackage $blockPackage)
	{
		return blx()->components->populateComponentByTypeAndPackage('block', $blockPackage);
	}
}
