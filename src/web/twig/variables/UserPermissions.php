<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;

Craft::$app->requireEdition(Craft::Pro);

/**
 * User permission functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class UserPermissions
{
    /**
     * Returns all of the known permissions, sorted by category.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        Craft::$app->getDeprecator()->log('craft.userPermissions.getAllPermissions()', '`craft.userPermissions.getAllPermissions()` has been deprecated. Use `craft.app.userPermissions.allPermissions` instead.');

        return Craft::$app->getUserPermissions()->getAllPermissions();
    }

    /**
     * Returns all of the group permissions a given user has.
     *
     * @param int $userId
     * @return array
     */
    public function getGroupPermissionsByUserId(int $userId): array
    {
        Craft::$app->getDeprecator()->log('craft.userPermissions.getGroupPermissionsByUserId()', '`craft.userPermissions.getGroupPermissionsByUserId()` has been deprecated. Use `craft.app.userPermissions.getGroupPermissionsByUserId()` instead.');

        return Craft::$app->getUserPermissions()->getGroupPermissionsByUserId($userId);
    }
}
