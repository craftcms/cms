<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\GqlException;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\ResolveInfo;
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
     * @since 3.4.0
     */
    public static function createFullAccessSchema(): GqlSchema
    {
        $permissionGroups = Craft::$app->getGql()->getAllPermissions();
        $schema = new GqlSchema(['name' => 'Full Schema', 'uid' => '*']);

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

    /**
     * Apply directives (if any) to a resolved value according to source and resolve info.
     *
     * @param $source
     * @param ResolveInfo $resolveInfo
     * @param $value
     * @return mixed
     */
    public static function applyDirectives($source, ResolveInfo $resolveInfo, $value)
    {
        if (isset($resolveInfo->fieldNodes[0]->directives)) {
            foreach ($resolveInfo->fieldNodes[0]->directives as $directive) {
                /** @var Directive $directiveEntity */
                $directiveEntity = GqlEntityRegistry::getEntity($directive->name->value);
                $arguments = [];

                // This can happen for built-in GraphQL directives in which case they will have been handled already, anyway
                if (!$directiveEntity) {
                    continue;
                }

                if (isset($directive->arguments[0])) {
                    foreach ($directive->arguments as $argument) {
                        $argumentValue = (!empty($argument->value->kind) && $argument->value->kind === 'Variable') ? $resolveInfo->variableValues[$argument->value->name->value] : $argument->value->value;
                        $arguments[$argument->name->value] = $argumentValue;
                    }
                }

                $value = $directiveEntity::apply($source, $value, $arguments, $resolveInfo);
            }
        }
        return $value;
    }
}
