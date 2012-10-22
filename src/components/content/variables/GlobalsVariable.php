<?php
namespace Blocks;

/**
 * Globals functions
 */
class GlobalsVariable
{
	/**
	 * Returns all global blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->globals->getAllBlocks();
	}

	/**
	 * Returns the total number of global blocks.
	 *
	 * @return int
	 */
	public function getTotalBlocks()
	{
		return blx()->globals->getTotalBlocks();
	}

	/**
	 * Gets a block by its ID.
	 *
	 * @param int $id
	 * @return GlobalBlockModel|null
	 */
	public function getBlockById($id)
	{
		return blx()->globals->getBlockById($id);
	}
}
