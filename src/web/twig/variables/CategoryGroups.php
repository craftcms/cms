<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\ArrayHelper;
use craft\models\CategoryGroup;

/**
 * Class CategoryGroupsVariable
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @deprecated in 3.0
 */
class CategoryGroups
{
    // Public Methods
    // =========================================================================

    /**
     * Returns all of the group IDs.
     *
     * @return int[]
     */
    public function getAllGroupIds(): array
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getAllGroupIds()', 'craft.categoryGroups.getAllGroupIds() has been deprecated. Use craft.app.categories.allGroupIds instead.');

        return Craft::$app->getCategories()->getAllGroupIds();
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return int[]
     */
    public function getEditableGroupIds(): array
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getEditableGroupIds()', 'craft.categoryGroups.getEditableGroupIds() has been deprecated. Use craft.app.categories.editableGroupIds instead.');

        return Craft::$app->getCategories()->getEditableGroupIds();
    }

    /**
     * Returns all category groups.
     *
     * @param string|null $indexBy
     * @return CategoryGroup[]
     */
    public function getAllGroups(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getAllGroups()', 'craft.categoryGroups.getAllGroups() has been deprecated. Use craft.app.categories.allGroups instead.');

        $groups = Craft::$app->getCategories()->getAllGroups();

        return $indexBy ? ArrayHelper::index($groups, $indexBy) : $groups;
    }

    /**
     * Returns all editable groups.
     *
     * @param string|null $indexBy
     * @return CategoryGroup[]
     */
    public function getEditableGroups(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getEditableGroups()', 'craft.categoryGroups.getEditableGroups() has been deprecated. Use craft.app.categories.editableGroups instead.');

        $groups = Craft::$app->getCategories()->getEditableGroups();

        return $indexBy ? ArrayHelper::index($groups, $indexBy) : $groups;
    }

    /**
     * Gets the total number of category groups.
     *
     * @return int
     */
    public function getTotalGroups(): int
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getTotalGroups()', 'craft.categoryGroups.getTotalGroups() has been deprecated. Use craft.app.categories.totalGroups instead.');

        return Craft::$app->getCategories()->getTotalGroups();
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @return CategoryGroup|null
     */
    public function getGroupById(int $groupId)
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getGroupById()', 'craft.categoryGroups.getGroupById() has been deprecated. Use craft.app.categories.getGroupById() instead.');

        return Craft::$app->getCategories()->getGroupById($groupId);
    }

    /**
     * Returns a group by its handle.
     *
     * @param string $groupHandle
     * @return CategoryGroup|null
     */
    public function getGroupByHandle(string $groupHandle)
    {
        Craft::$app->getDeprecator()->log('craft.categoryGroups.getGroupByHandle()', 'craft.categoryGroups.getGroupByHandle() has been deprecated. Use craft.app.categories.getGroupByHandle() instead.');

        return Craft::$app->getCategories()->getGroupByHandle($groupHandle);
    }
}
