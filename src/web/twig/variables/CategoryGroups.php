<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\models\CategoryGroup;

/**
 * Class CategoryGroupsVariable
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
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
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getAllGroupIds()', 'craft.categoryGroups.getAllGroupIds() has been deprecated. Use craft.app.categories.allGroupIds instead.');

        return Craft::$app->getCategories()->getAllGroupIds();
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return integer[]
     */
    public function getEditableGroupIds()
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getEditableGroupIds()', 'craft.categoryGroups.getEditableGroupIds() has been deprecated. Use craft.app.categories.editableGroupIds() instead.');

        return Craft::$app->getCategories()->getEditableGroupIds();
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
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getAllGroups()', 'craft.categoryGroups.getAllGroups() has been deprecated. Use craft.app.categories.getAllGroups() instead.');

        return Craft::$app->getCategories()->getAllGroups($indexBy);
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
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getEditableGroups()', 'craft.categoryGroups.getEditableGroups() has been deprecated. Use craft.app.categories.getEditableGroups() instead.');

        return Craft::$app->getCategories()->getEditableGroups($indexBy);
    }

    /**
     * Gets the total number of category groups.
     *
     * @return integer
     */
    public function getTotalGroups()
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getTotalGroups()', 'craft.categoryGroups.getTotalGroups() has been deprecated. Use craft.app.categories.totalGroups instead.');

        return Craft::$app->getCategories()->getTotalGroups();
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
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getGroupById()', 'craft.categoryGroups.getGroupById() has been deprecated. Use craft.app.categories.getGroupById() instead.');

        return Craft::$app->getCategories()->getGroupById($groupId);
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
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getGroupByHandle()', 'craft.categoryGroups.getGroupByHandle() has been deprecated. Use craft.app.categories.getGroupByHandle() instead.');

        return Craft::$app->getCategories()->getGroupByHandle($groupHandle);
    }
}
