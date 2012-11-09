<?php
namespace Blocks;

/**
 * User management functions
 */
class UserProfileBlocksVariable
{
	/**
	 * Returns all user blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->users->getAllBlocks();
	}

	/**
	 * Gets a user profile block by its ID.
	 *
	 * @param int $id
	 * @return UserProfileBlockModel
	 */
	public function getBlockById($id)
	{
		return blx()->users->getBlockById($id);
	}
}
