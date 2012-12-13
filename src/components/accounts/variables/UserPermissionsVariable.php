<?php
namespace Blocks;

/**
 * User permission functions
 */
class UserPermissionsVariable
{
	/**
	 * Returns all of the known permissions, sorted by category.
	 *
	 * @return array
	 */
	public function getAllPermissions()
	{
		return blx()->userPermissions->getAllPermissions();
	}

	/**
	 * Returns all of the group permissions a given user has.
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getGroupPermissionsByUserId($userId)
	{
		return blx()->userPermissions->getGroupPermissionsByUserId($userId);
	}
}
