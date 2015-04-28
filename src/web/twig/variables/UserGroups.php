<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\models\UserGroup as UserGroupModel;

\Craft::$app->requireEdition(\Craft::Pro);

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
		return \Craft::$app->getUserGroups()->getAllGroups($indexBy);
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
		return \Craft::$app->getUserGroups()->getGroupById($groupId);
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
		return \Craft::$app->getUserGroups()->getGroupByHandle($groupHandle);
	}
}
