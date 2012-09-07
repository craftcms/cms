<?php
namespace Blocks;

/**
 *
 */
class BlocksService extends ApplicationComponent
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
	 * @return mixed
	 */
	public function getBlockByClass($class)
	{
		return blx()->components->getComponentByTypeAndClass('block', $class);
	}
}
