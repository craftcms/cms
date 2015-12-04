<?php
namespace Craft;

/**
 * Class CategoryGroupsVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     2.4
 */
class CategoryGroupsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all of the group IDs.
	 *
	 * @return array
	 */
	public function getAllGroupIds()
	{
		return craft()->categories->getAllGroupIds();
	}

	/**
	 * Returns all of the category group IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableGroupIds()
	{
		return craft()->categories->getEditableGroupIds();
	}

	/**
	 * Returns all category groups.
	 *
	 * @param null|string $indexBy
	 *
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		return craft()->categories->getAllGroups($indexBy);
	}

	/**
	 * Returns all editable groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getEditableGroups($indexBy = null)
	{
		return craft()->categories->getEditableGroups($indexBy);
	}

	/**
	 * Gets the total number of category groups.
	 *
	 * @return int
	 */
	public function getTotalGroups()
	{
		return craft()->categories->getTotalGroups();
	}

	/**
	 * Returns a group by its ID.
	 *
	 * @param $groupId
	 *
	 * @return CategoryGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		return craft()->categories->getGroupById($groupId);
	}

	/**
	 * Returns a group by its handle.
	 *
	 * @param $groupHandle
	 *
	 * @return CategoryGroupModel|null
	 */
	public function getGroupByHandle($groupHandle)
	{
		return craft()->categories->getGroupByHandle($groupHandle);
	}
}
