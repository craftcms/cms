<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Illuminate\Support\Facades\Log;

class GqlHelper
{
    /**
     * @param  string|string[]  $components  The component(s) to check.
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function isSchemaAwareOf(array|string $components, ?GqlSchema $schema = null): bool
    {
        try {
            $schema = self::_schema($schema);
        } catch (GqlException) {
            return false;
        }

        foreach ((array) $components as $component) {
            $component = strtolower($component);
            $found = array_any($schema->scope, fn ($scopeComponent) => str_starts_with(strtolower((string) $scopeComponent), $component));
            if (! $found) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  string  $action  The action for which the entities should be extracted. Defaults to "read".
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function extractAllowedEntitiesFromSchema(string $action = 'read', ?GqlSchema $schema = null): array
    {
        try {
            $schema = self::_schema($schema);
        } catch (GqlException) {
            return [];
        }

        return $schema->getAllScopePairsForAction($action);
    }

    /**
     * @param  string  $component  The component to check.
     * @param  string  $action  The action. Defaults to "read".
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     *
     * @throws GqlException
     */
    public static function canSchema(string $component, string $action = 'read', ?GqlSchema $schema = null): bool
    {
        try {
            $schema = self::_schema($schema);
        } catch (GqlException) {
            return false;
        }

        $search = strtolower("$component:$action");

        return array_any($schema->scope, fn ($scopeComponent) => strtolower((string) $scopeComponent) === $search);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function extractEntityAllowedActions(string $entity, ?GqlSchema $schema = null): array
    {
        try {
            $schema = self::_schema($schema);
        } catch (GqlException) {
            return [];
        }

        $actions = [];
        $search = sprintf('%s:', strtolower($entity));
        $searchLen = strlen($search);

        foreach ($schema->scope as $scopeComponent) {
            if (str_starts_with(strtolower((string) $scopeComponent), $search)) {
                $action = substr((string) $scopeComponent, $searchLen);
                $actions[$action] = true;
            }
        }

        return array_keys($actions);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canMutateAssets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('edit', $schema);

        return isset($allowedEntities['volumes']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryEntries(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return
            isset($allowedEntities['sections']) ||
            isset($allowedEntities['nestedentryfields']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryAssets(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['volumes']);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryUsers(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['usergroups']);
    }

    /**
     * @param  string  $typeName  The union type name.
     * @param  array  $includedTypes  The type the union should include
     * @param  callable|null  $resolveFunction  The resolver function to use to resolve a specific type. If not provided,
     *                                          a default one will be used that is able to resolve Craft elements.
     */
    public static function getUnionType(string $typeName, array $includedTypes, ?callable $resolveFunction = null): mixed
    {
        $resolveFunction ??= fn (ElementInterface $value) => $value->getGqlTypeName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new UnionType([
            'name' => $typeName,
            'types' => $includedTypes,
            'resolveType' => $resolveFunction,
        ]));
    }

    public static function wrapInNonNull(mixed $type): mixed
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

    public static function createFullAccessSchema(): GqlSchema
    {
        $groups = app(GqlService::class)->getAllSchemaComponents();
        $schema = new GqlSchema(['name' => 'Full Schema', 'uid' => '*']);

        // Fetch all nested components
        $traverser = function ($group) use ($schema, &$traverser) {
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

        $schema->scope[] = 'directive:parseRefs';
        $schema->scope[] = 'directive:transform';

        return $schema;
    }

    public static function applyDirectives(mixed $source, ResolveInfo $resolveInfo, mixed $value): mixed
    {
        /** @phpstan-ignore-next-line */
        if (! isset($resolveInfo->fieldNodes[0]->directives)) {
            return $value;
        }

        foreach ($resolveInfo->fieldNodes[0]->directives as $directive) {
            /** @var Directive|false $directiveEntity */
            $directiveEntity = GqlEntityRegistry::getEntity($directive->name->value);

            // This can happen for built-in GraphQL directives in which case they will have been handled already, anyway
            if (! $directiveEntity) {
                continue;
            }

            $arguments = [];

            if (isset($directive->arguments[0])) {
                foreach ($directive->arguments as $argument) {
                    $arguments[$argument->name->value] = self::_convertArgumentValue($argument->value, $resolveInfo->variableValues);
                }
            }

            $value = $directiveEntity::apply($source, $value, $arguments, $resolveInfo);
        }

        return $value;
    }

    public static function prepareTransformArguments(array $arguments): array|string
    {
        unset($arguments['immediately']);

        return $arguments['handle'] ?? $arguments;
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryDrafts(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['elements']) && is_array($allowedEntities['elements']) && in_array('drafts', $allowedEntities['elements'], true);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryRevisions(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['elements']) && is_array($allowedEntities['elements']) && in_array('revisions', $allowedEntities['elements'], true);
    }

    /**
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryInactiveElements(?GqlSchema $schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['elements']) && is_array($allowedEntities['elements']) && in_array('inactive', $allowedEntities['elements'], true);
    }

    /**
     * @return Site[]
     */
    public static function getAllowedSites(?GqlSchema $schema = null): array
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);
        $allowedSiteUids = $allowedEntities['sites'] ?? [];

        return Sites::getAllSites(true)
            ->filter(fn (Site $site) => in_array($site->uid, $allowedSiteUids, true))
            ->values()
            ->all();
    }

    private static function _convertArgumentValue(mixed $value, array $variableValues = []): mixed
    {
        if ($value instanceof VariableNode) {
            return $variableValues[$value->name->value];
        }

        if ($value instanceof ListValueNode) {
            return array_map(fn ($node) => self::_convertArgumentValue($node), iterator_to_array($value->values));
        }

        return $value->value;
    }

    public static function getFieldNameWithAlias(ResolveInfo $resolveInfo, mixed $source, ?array $context): string
    {
        $fieldName = is_array($resolveInfo->path) ? array_slice($resolveInfo->path, -1)[0] : $resolveInfo->fieldName;
        $isAlias = $fieldName !== $resolveInfo->fieldName;

        /** @var ElementQueryConditionBuilder|null $conditionBuilder */
        $conditionBuilder = $context['conditionBuilder'] ?? null;

        if ($isAlias) {
            $cannotBeAliased = $conditionBuilder && ! $conditionBuilder->canNodeBeAliased($fieldName);
            $aliasNotEagerLoaded = ! $source instanceof ElementInterface || $source->getEagerLoadedElements($fieldName) === null;

            if ($cannotBeAliased || $aliasNotEagerLoaded) {
                $fieldName = $resolveInfo->fieldName;
            }
        }

        return $fieldName;
    }

    public static function eagerLoadComplexity(): callable
    {
        return static fn ($childComplexity) => $childComplexity + GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD;
    }

    public static function singleQueryComplexity(): callable
    {
        return static fn ($childComplexity) => $childComplexity + GqlService::GRAPHQL_COMPLEXITY_QUERY;
    }

    /**
     * @param  int  $baseComplexity  The base complexity to use. Defaults to a single query.
     */
    public static function relatedArgumentComplexity(int $baseComplexity = GqlService::GRAPHQL_COMPLEXITY_QUERY): callable
    {
        return static function ($childComplexity, $args) use ($baseComplexity) {
            $complexityScore = $childComplexity + $baseComplexity;

            foreach (array_keys($args) as $argumentName) {
                if (str_starts_with($argumentName, 'relatedTo')) {
                    $complexityScore += GqlService::GRAPHQL_COMPLEXITY_QUERY;
                }
            }

            return $complexityScore;
        };
    }

    public static function nPlus1Complexity(): callable
    {
        return static fn ($childComplexity) => $childComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1;
    }

    /**
     * @return EntryType[]
     */
    public static function getSchemaContainedEntryTypes(?GqlSchema $schema = null): array
    {
        $entryTypes = [];

        foreach (static::getSchemaContainedSections($schema) as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $entryTypes[$entryType->uid] = $entryType;
            }
        }

        foreach (static::getSchemaContainedNestedEntryFields($schema) as $field) {
            foreach ($field->getFieldLayoutProviders() as $provider) {
                if ($provider instanceof EntryType) {
                    $entryTypes[$provider->uid] = $provider;
                }
            }
        }

        return array_values($entryTypes);
    }

    /**
     * @return Section[]
     */
    public static function getSchemaContainedSections(?GqlSchema $schema = null): array
    {
        return Sections::getAllSections()
            ->filter(fn (Section $section) => static::isSchemaAwareOf("sections.$section->uid", $schema))
            ->all();
    }

    /**
     * @return ElementContainerFieldInterface[]
     */
    public static function getSchemaContainedNestedEntryFields(?GqlSchema $schema = null): array
    {
        $fieldsService = app(Fields::class);
        /** @var ElementContainerFieldInterface[] $fields */
        $fields = array_merge(...array_map(
            fn (string $type) => $fieldsService->getFieldsByType($type)->all(),
            $fieldsService->getNestedEntryFieldTypes()->all()
        ));

        return array_filter($fields, fn (ElementContainerFieldInterface $field) => static::isSchemaAwareOf("nestedentryfields.$field->uid", $schema));
    }

    /**
     * @throws GqlException
     */
    private static function _schema(?GqlSchema $schema): GqlSchema
    {
        if ($schema !== null) {
            return $schema;
        }

        try {
            return app(GqlService::class)->getActiveSchema();
        } catch (GqlException $e) {
            Log::warning("Could not get the active GraphQL schema: {$e->getMessage()}", [__METHOD__]);
            report($e);
            throw $e;
        }
    }

    public static function isIntrospectionQuery(string $query): bool
    {
        // strtok() won’t find a token if the string starts with it
        $tok = strtok(" $query", '{');
        if ($tok === false) {
            return false;
        }
        $tok = strtok('({}');
        if ($tok === false) {
            return false;
        }
        $tok = trim($tok);

        return in_array($tok, ['__schema', '__type']);
    }
}
