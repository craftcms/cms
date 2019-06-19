<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\GqlException;
use craft\services\ProjectConfig as ProjectConfigService;

/**
 * Class Gql
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class Gql
{
    /**
     * Returns true if the current token is aware of the provided scope(s).
     *
     * @param string|string[] $scopes The scope(s) to check.
     * @return bool
     * @throws GqlException
     */
    public static function isTokenAwareOf($scopes): bool
    {
        if (!is_array($scopes)) {
            $scopes = [$scopes];
        }

        $permissions = Craft::$app->getGql()->getCurrentToken()->permissions;

        foreach ($scopes as $scope) {
            if (empty(preg_grep('/^' . preg_quote($scope, '/') . '\:/i', $permissions))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extracts all the allowed entities from the token permissions for the action.
     *
     * @param $action
     * @return array
     * @throws GqlException
     */
    public static function extractAllowedEntitiesFromToken($action): array
    {
        $permissions = Craft::$app->getGql()->getCurrentToken()->permissions;
        $pairs = [];

        foreach ($permissions as $permission) {
            // Check if this is for the requested action
            if (StringHelper::endsWith($permission, ':' . $action)) {
                $permission = StringHelper::removeRight($permission, ':' . $action);

                $parts = explode('.', $permission);

                if (count($parts) === 2) {
                    $pairs[$parts[0]][] = $parts[1];
                }
            }
        }

        return $pairs;
    }
}
