<?php
namespace Blocks;

/**
 * Global blocks functions
 */
class GlobalBlocksVariable
{
	/**
	 * Returns all global blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->globalBlocks->getAllBlocks();
	}

	/**
	 * Returns the total number of global blocks.
	 *
	 * @return int
	 */
	public function getTotalBlocks()
	{
		return blx()->globalBlocks->getTotalBlocks();
	}

	/**
	 * Gets a block by its ID.
	 *
	 * @param int $id
	 * @return GlobalBlockModel|null
	 */
	public function getBlockById($id)
	{
		return blx()->globalBlocks->getBlockById($id);
	}
}
