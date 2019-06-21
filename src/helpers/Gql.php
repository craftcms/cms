<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\GqlException;

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

        try {
            $permissions = (array) Craft::$app->getGql()->getCurrentToken()->permissions;
        } catch (GqlException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return false;
        }

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
     * @param string $action The action for which the entities should be extracted. Defaults to "read"
     * @return array
     */
    public static function extractAllowedEntitiesFromToken($action = 'read'): array
    {
        try {
            $permissions = (array) Craft::$app->getGql()->getCurrentToken()->permissions;
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
        } catch (GqlException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return [];
        }
    }

    /**
     * Return true if current token can query entries.
     *
     * @return bool
     */
    public static function canQueryEntries(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromToken();
        return isset($allowedEntities['sections'], $allowedEntities['entrytypes']);
    }

    /**
     * Return true if current token can query entries.
     *
     * @return bool
     */
    public static function canQueryAssets(): bool
    {
        return isset(self::extractAllowedEntitiesFromToken()['volumes']);
    }

    /**
     * Return true if current token can query entries.
     *
     * @return bool
     */
    public static function canQueryGlobalSets(): bool
    {
        return isset(self::extractAllowedEntitiesFromToken()['globalsets']);
    }

    /**
     * Return true if current token can query entries.
     *
     * @return bool
     */
    public static function canQueryUsers(): bool
    {
        return isset(self::extractAllowedEntitiesFromToken()['usergroups']);
    }
}
