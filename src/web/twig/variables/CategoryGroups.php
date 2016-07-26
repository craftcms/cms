<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\models\CategoryGroup;

/**
 * Class CategoryGroupsVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     3.0
 */
class CategoryGroups
{
    // Public Methods
    // =========================================================================

    /**
     * Returns all of the group IDs.
     *
     * @return integer[]
     */
    public function getAllGroupIds()
    {
        return \Craft::$app->getCategories()->getAllGroupIds();
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return integer[]
     */
    public function getEditableGroupIds()
    {
        return \Craft::$app->getCategories()->getEditableGroupIds();
    }

    /**
     * Returns all category groups.
     *
     * @param null|string $indexBy
     *
     * @return CategoryGroup[]
     */
    public function getAllGroups($indexBy = null)
    {
        return \Craft::$app->getCategories()->getAllGroups($indexBy);
    }

    /**
     * Returns all editable groups.
     *
     * @param string|null $indexBy
     *
     * @return CategoryGroup[]
     */
    public function getEditableGroups($indexBy = null)
    {
        return \Craft::$app->getCategories()->getEditableGroups($indexBy);
    }

    /**
     * Gets the total number of category groups.
     *
     * @return integer
     */
    public function getTotalGroups()
    {
        return \Craft::$app->getCategories()->getTotalGroups();
    }

    /**
     * Returns a group by its ID.
     *
     * @param $groupId
     *
     * @return CategoryGroup|null
     */
    public function getGroupById($groupId)
    {
        return \Craft::$app->getCategories()->getGroupById($groupId);
    }

    /**
     * Returns a group by its handle.
     *
     * @param $groupHandle
     *
     * @return CategoryGroup|null
     */
    public function getGroupByHandle($groupHandle)
    {
        return \Craft::$app->getCategories()->getGroupByHandle($groupHandle);
    }
}
