<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\GqlException;
use craft\gql\base\Directive;
use craft\gql\ElementQueryConditionBuilder;
use craft\gql\GqlEntityRegistry;
use craft\models\GqlSchema;
use craft\services\Gql as GqlService;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
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
     * Returns whether the given component(s) are included in a schema’s scope.
     *
     * @param string|string[] $components The component(s) to check.
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function isSchemaAwareOf($components, ?GqlSchema $schema = null): bool
    {
        try {
            $schema = static::_schema($schema);
        } catch (GqlException $e) {
            return false;
        }

        if (!is_array($components)) {
            $components = [$components];
        }

        $scope = (array)$schema->scope;

        foreach ($components as $component) {
            if (empty(preg_grep('/^' . preg_quote($component, '/') . '\:/i', $scope))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extracts all the allowed entities from a schema for the given action.
     *
     * @param string $action The action for which the entities should be extracted. Defaults to "read".
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return array
     */
    public static function extractAllowedEntitiesFromSchema(string $action = 'read', ?GqlSchema $schema = null): array
    {
        try {
            $schema = static::_schema($schema);
        } catch (GqlException $e) {
            return [];
        }

        return $schema->getAllScopePairsForAction($action);
    }

    /**
     * Returns whether the given component is included in a schema, for the given action.
     *
     * @param string $component The component to check.
     * @param string $action The action. Defaults to "read".
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     * @throws GqlException
     */
    public static function canSchema(string $component, $action = 'read', ?GqlSchema $schema = null): bool
    {
        try {
            $schema = static::_schema($schema);
        } catch (GqlException $e) {
            return false;
        }

        return !empty(preg_grep('/^' . preg_quote($component, '/') . '\:' . preg_quote($action, '/') . '$/i', (array)$schema->scope));
    }

    /**
     * Return a list of all the actions the current schema is allowed for a given entity.
     *
     * @param string $entity
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return array
     */
    public static function extractEntityAllowedActions(string $entity, ?GqlSchema $schema = null): array
    {
        try {
            $schema = static::_schema($schema);
        } catch (GqlException $e) {
            return [];
        }

        $scope = (array)$schema->scope;
        $actions = [];

        foreach (preg_grep('/^' . preg_quote($entity, '/') . '\:.*$/i', $scope) as $component) {
            $parts = explode(':', $component);
            $actions[end($parts)] = true;
        }

        return array_keys($actions);
    }

    /**
     * Return true if active schema can mutate entries.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     * @since 3.5.0
     */
    public static function canMutateEntries(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        // Singles don't have the `edit` action.
        if (!isset($allowedEntities['entrytypes'])) {
            $allowedEntities = self::extractAllowedEntitiesFromSchema('save', $schema);
        }

        return isset($allowedEntities['entrytypes']);
    }

    /**
     * Return true if active schema can mutate tags.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     * @since 3.5.0
     */
    public static function canMutateTags(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);
        return isset($allowedEntities['taggroups']);
    }

    /**
     * Return true if active schema can mutate global sets.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     * @since 3.5.0
     */
    public static function canMutateGlobalSets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);
        return isset($allowedEntities['globalsets']);
    }

    /**
     * Return true if active schema can mutate categories.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     * @since 3.5.0
     */
    public static function canMutateCategories(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);
        return isset($allowedEntities['categorygroups']);
    }

    /**
     * Return true if active schema can mutate assets.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     * @since 3.5.0
     */
    public static function canMutateAssets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);
        return isset($allowedEntities['volumes']);
    }

    /**
     * Return true if active schema can query entries.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function canQueryEntries(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        return isset($allowedEntities['sections'], $allowedEntities['entrytypes']);
    }

    /**
     * Return true if active schema can query assets.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function canQueryAssets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        return isset($allowedEntities['volumes']);
    }

    /**
     * Return true if active schema can query categories.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function canQueryCategories(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        return isset($allowedEntities['categorygroups']);
    }

    /**
     * Return true if active schema can query tags.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function canQueryTags(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        return isset($allowedEntities['taggroups']);
    }

    /**
     * Return true if active schema can query global sets.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function canQueryGlobalSets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        return isset($allowedEntities['globalsets']);
    }

    /**
     * Return true if active schema can query users.
     *
     * @param GqlSchema|null $schema The GraphQL schema. If none is provided, the active schema will be used.
     * @return bool
     */
    public static function canQueryUsers(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        return isset($allowedEntities['usergroups']);
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
     * Wrap a GQL object type in a NonNull type.
     *
     * @param $type
     * @return mixed
     */
    public static function wrapInNonNull($type)
    {
        if ($type instanceof NonNull) {
            return $type;
        }

        if (is_array($type)) {
            $type['type'] = Type::nonNull($type['type']);
        } else {
            $type = Type::nonNull($type);
        }

        return $type;
    }

    /**
     * Creates a temporary schema with full access to the GraphQL API.
     *
     * @return GqlSchema
     * @since 3.4.0
     */
    public static function createFullAccessSchema(): GqlSchema
    {
        $groups = Craft::$app->getGql()->getAllSchemaComponents();
        $schema = new GqlSchema(['name' => 'Full Schema', 'uid' => '*']);

        // Fetch all nested components
        $traverser = function($group) use ($schema, &$traverser) {
            foreach ($group as $component => $config) {
                $schema->scope[] = $component;

                if (isset($config['nested'])) {
                    $traverser($config['nested']);
                }
            }
        };

        foreach ($groups['queries'] as $group) {
            $traverser($group);
        }

        foreach ($groups['mutations'] as $group) {
            $traverser($group);
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
                        $arguments[$argument->name->value] = self::_convertArgumentValue($argument->value, $resolveInfo->variableValues);
                    }
                }

                $value = $directiveEntity::apply($source, $value, $arguments, $resolveInfo);
            }
        }
        return $value;
    }

    /**
     * Prepare arguments intended for Asset transforms.
     *
     * @param array $arguments
     * @return array|string
     * @since 3.5.3
     */
    public static function prepareTransformArguments(array $arguments)
    {
        unset($arguments['immediately']);

        if (!empty($arguments['handle'])) {
            $transform = $arguments['handle'];
        } else if (!empty($arguments['transform'])) {
            $transform = $arguments['transform'];
        } else {
            $transform = $arguments;
        }

        return $transform;
    }

    /**
     * @param ValueNode|VariableNode $value
     * @param array $variableValues
     * @return array|array[]|mixed
     */
    private static function _convertArgumentValue($value, array $variableValues = [])
    {
        if ($value instanceof VariableNode) {
            return $variableValues[$value->name->value];
        }

        if ($value instanceof ListValueNode) {
            return array_map(function($node) {
                return self::_convertArgumentValue($node);
            }, iterator_to_array($value->values));
        }

        return $value->value;
    }

    /**
     * Looking at the resolve information and the source queried, return the field name or it's alias, if used.
     *
     * @param ResolveInfo $resolveInfo
     * @param $source
     * @param $context
     * @return string
     */
    public static function getFieldNameWithAlias(ResolveInfo $resolveInfo, $source, $context): string
    {
        $fieldName = is_array($resolveInfo->path) ? array_slice($resolveInfo->path, -1)[0] : $resolveInfo->fieldName;
        $isAlias = $fieldName !== $resolveInfo->fieldName;

        /** @var ElementQueryConditionBuilder $conditionBuilder */
        $conditionBuilder = $context['conditionBuilder'] ?? null;

        if ($isAlias) {
            $cannotBeAliased = $conditionBuilder && !$conditionBuilder->canNodeBeAliased($fieldName);
            $aliasNotEagerLoaded = !$source instanceof ElementInterface || $source->getEagerLoadedElements($fieldName) === null;

            if ($cannotBeAliased || $aliasNotEagerLoaded) {
                $fieldName = $resolveInfo->fieldName;
            }
        }

        return $fieldName;
    }

    /**
     * Shorthand for returning the complexity function for an eager-loaded field.
     *
     * @return callable
     * @since 3.6.0
     */
    public static function eagerLoadComplexity(): callable
    {
        return static function($childComplexity) {
            return $childComplexity + GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD;
        };
    }

    /**
     * Shorthand for returning the complexity function for a field that will add a single query to execution.
     *
     * @return callable
     * @since 3.6.0
     */
    public static function singleQueryComplexity(): callable
    {
        return static function($childComplexity) {
            return $childComplexity + GqlService::GRAPHQL_COMPLEXITY_QUERY;
        };
    }

    /**
     * Shorthand for returning the complexity function for a field that will generate a single query for every iteration.
     *
     * @return callable
     * @since 3.6.0
     */
    public static function nPlus1Complexity(): callable
    {
        return static function($childComplexity) {
            return $childComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1;
        };
    }

    /**
     * @param GqlSchema|null $schema
     * @return GqlSchema
     * @throws GqlException
     */
    private static function _schema(?GqlSchema $schema): GqlSchema
    {
        if ($schema !== null) {
            return $schema;
        }

        try {
            return Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException $e) {
            Craft::warning("Could not get the active GraphQL schema: {$e->getMessage()}", __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            throw $e;
        }
    }
}
