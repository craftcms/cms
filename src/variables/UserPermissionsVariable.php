<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * User permission functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class UserPermissionsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all of the known permissions, sorted by category.
	 *
	 * @return array
	 */
	public function getAllPermissions()
	{
		return craft()->userPermissions->getAllPermissions();
	}

	/**
	 * Returns the permissions that the current user is allowed to assign to another user.
	 *
	 * @param UserModel|null $user The recipient of the permissions. If set, their current permissions will be included as well.
	 *
	 * @return array
	 */
	public function getAssignablePermissions(UserModel $user = null)
	{
		return craft()->userPermissions->getAssignablePermissions($user);
	}

	/**
	 * Returns all of the group permissions a given user has.
	 *
	 * @param int $userId
	 *
	 * @return array
	 */
	public function getGroupPermissionsByUserId($userId)
	{
		return craft()->userPermissions->getGroupPermissionsByUserId($userId);
	}
}
