<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\models\UserGroup;

Craft::$app->requireEdition(Craft::Pro);

/**
 * User group functions.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
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
        Craft::$app->getDeprecator()->log('craft.userGroups.getAllGroups()', 'craft.userGroups.getAllGroups() has been deprecated. Use craft.app.userGroups.getAllGroups() instead.');

        return Craft::$app->getUserGroups()->getAllGroups($indexBy);
    }

    /**
     * Gets a user group by its ID.
     *
     * @param integer $groupId
     *
     * @return UserGroup|null
     */
    public function getGroupById($groupId)
    {
        Craft::$app->getDeprecator()->log('craft.userGroups.getGroupById()', 'craft.userGroups.getGroupById() has been deprecated. Use craft.app.userGroups.getGroupById() instead.');

        return Craft::$app->getUserGroups()->getGroupById($groupId);
    }

    /**
     * Gets a user group by its handle.
     *
     * @param string $groupHandle
     *
     * @return UserGroup|null
     */
    public function getGroupByHandle($groupHandle)
    {
        Craft::$app->getDeprecator()->log('craft.userGroups.getGroupByHandle()', 'craft.userGroups.getGroupByHandle() has been deprecated. Use craft.app.userGroups.getGroupByHandle() instead.');

        return Craft::$app->getUserGroups()->getGroupByHandle($groupHandle);
    }
}
