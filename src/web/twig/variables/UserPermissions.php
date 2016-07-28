<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;

Craft::$app->requireEdition(Craft::Client);

/**
 * User permission functions.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class UserPermissions
{
    // Public Methods
    // =========================================================================

    /**
     * Constructor
     */
    public function __construct()
    {
        Craft::$app->getDeprecator()->log('craft.userPermissions', 'craft.userPermissions has been deprecated. Use craft.app.userPermissions instead.');
    }

    /**
     * Returns all of the known permissions, sorted by category.
     *
     * @return array
     */
    public function getAllPermissions()
    {
        return Craft::$app->getUserPermissions()->getAllPermissions();
    }

    /**
     * Returns all of the group permissions a given user has.
     *
     * @param integer $userId
     *
     * @return array
     */
    public function getGroupPermissionsByUserId($userId)
    {
        return Craft::$app->getUserPermissions()->getGroupPermissionsByUserId($userId);
    }
}
