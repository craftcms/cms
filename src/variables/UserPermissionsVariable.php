<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\Craft;

craft()->requireEdition(Craft::Pro);

/**
 * User permission functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
