<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\Craft;
use craft\app\models\UserGroup as UserGroupModel;

craft()->requireEdition(Craft::Pro);

/**
 * User group functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroups
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all user groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		return craft()->userGroups->getAllGroups($indexBy);
	}

	/**
	 * Gets a user group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return UserGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		return craft()->userGroups->getGroupById($groupId);
	}

	/**
	 * Gets a user group by its handle.
	 *
	 * @param string $groupHandle
	 *
	 * @return UserGroupModel|null
	 */
	public function getGroupByHandle($groupHandle)
	{
		return craft()->userGroups->getGroupByHandle($groupHandle);
	}
}
