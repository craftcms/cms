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
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
        return Craft::$app->getUserGroups()->getGroupByHandle($groupHandle);
    }
}
