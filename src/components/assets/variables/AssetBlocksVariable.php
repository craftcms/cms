<?php
namespace Blocks;

/**
 * Content functions
 */
class AssetBlocksVariable
{
	/**
	 * Returns all asset blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->assets->getAllBlocks();
	}

	/**
	 * Gets a asset block by its ID.
	 *
	 * @param int $id
	 * @return AssetBlockModel
	 */
	public function getBlockById($id)
	{
		return blx()->assets->getBlockById($id);
	}
}
