<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * User group functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class UserGroupsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all user groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return UserGroupModel[]
	 */
	public function getAllGroups($indexBy = null)
	{
		return craft()->userGroups->getAllGroups($indexBy);
	}

	/**
	 * Returns the user groups that the current user is allowed to assign to another user.
	 *
	 * @param UserModel|null $user The recipient of the user groups. If set, their current groups will be included as well.
	 *
	 * @return UserGroupModel[]
	 */
	public function getAssignableGroups(UserModel $user = null)
	{
		return craft()->userGroups->getAssignableGroups($user);
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
