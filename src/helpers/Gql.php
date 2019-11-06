<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\GqlException;
use craft\gql\GqlEntityRegistry;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\UnionType;

/**
 * Class Gql
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Gql
{
    /**
     * Returns true if the active schema is aware of the provided scope(s).
     *
     * @param string|string[] $scopes The scope(s) to check.
     * @return bool
     */
    public static function isSchemaAwareOf($scopes): bool
    {
        if (!is_array($scopes)) {
            $scopes = [$scopes];
        }

        try {
            $permissions = (array)Craft::$app->getGql()->getActiveSchema()->scope;
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
     * Extracts all the allowed entities from the active schema for the action.
     *
     * @param string $action The action for which the entities should be extracted. Defaults to "read"
     * @return array
     */
    public static function extractAllowedEntitiesFromSchema($action = 'read'): array
    {
        try {
            return Craft::$app->getGql()->getActiveSchema()->getAllScopePairsForAction($action);
        } catch (GqlException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return [];
        }
    }

    /**
     * Returns true if the active schema can perform the action on the scope.
     *
     * @param string $scope The scope to check.
     * @param string $action The action. Defaults to "read"
     * @return bool
     * @throws GqlException
     */
    public static function canSchema($scope, $action = 'read'): bool
    {
        try {
            $permissions = (array)Craft::$app->getGql()->getActiveSchema()->scope;
            return !empty(preg_grep('/^' . preg_quote($scope, '/') . '\:' . preg_quote($action, '/') . '$/i', $permissions));
        } catch (GqlException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            return false;
        }
    }

    /**
     * Return true if active schema can query entries.
     *
     * @return bool
     */
    public static function canQueryEntries(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();
        return isset($allowedEntities['sections'], $allowedEntities['entrytypes']);
    }

    /**
     * Return true if active schema can query assets.
     *
     * @return bool
     */
    public static function canQueryAssets(): bool
    {
        return isset(self::extractAllowedEntitiesFromSchema()['volumes']);
    }

    /**
     * Return true if active schema can query categories.
     *
     * @return bool
     */
    public static function canQueryCategories(): bool
    {
        return isset(self::extractAllowedEntitiesFromSchema()['categorygroups']);
    }

    /**
     * Return true if active schema can query tags.
     *
     * @return bool
     */
    public static function canQueryTags(): bool
    {
        return isset(self::extractAllowedEntitiesFromSchema()['taggroups']);
    }

    /**
     * Return true if active schema can query global sets.
     *
     * @return bool
     */
    public static function canQueryGlobalSets(): bool
    {
        return isset(self::extractAllowedEntitiesFromSchema()['globalsets']);
    }

    /**
     * Return true if active schema can query users.
     *
     * @return bool
     */
    public static function canQueryUsers(): bool
    {
        return isset(self::extractAllowedEntitiesFromSchema()['usergroups']);
    }

    /**
     * Get (and create if needed) a union type by name, included types and a resolver funcion.
     *
     * @param string $typeName The union type name.
     * @param array $includedTypes The type the union should include
     * @param callable $resolveFunction The resolver function to use to resolve a specific type.
     * @return mixed
     */
    public static function getUnionType(string $typeName, array $includedTypes, callable $resolveFunction)
    {
        $unionType = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new UnionType([
            'name' => $typeName,
            'types' => $includedTypes,
            'resolveType' => $resolveFunction,
        ]));

        return $unionType;
    }

    /**
     * Creates a temporary schema with full access to the GraphQL API.
     *
     * @return GqlSchema
     * @since 3.3.12
     */
    public static function createFullAccessSchema(): GqlSchema
    {
        $permissionGroups = Craft::$app->getGql()->getAllPermissions();

        $schema = new GqlSchema([
            'uid' => '*',
            'name' => Craft::t('app', 'Full Schema'),
            'accessToken' => '*',
            'enabled' => true,
            'isTemporary' => true,
            'scope' => []
        ]);

        // Fetch all nested permissions
        $traverser = function($permissions) use ($schema, &$traverser) {
            foreach ($permissions as $permission => $config) {
                $schema->scope[] = $permission;
                if (isset($config['nested'])) {
                    $traverser($config['nested']);
                }
            }
        };

        foreach ($permissionGroups as $permissionGroup) {
            $traverser($permissionGroup);
        }

        return $schema;
    }
}
