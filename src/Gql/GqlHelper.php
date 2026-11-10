<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Error\Error;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Values;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\AST;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use WeakMap;

/**
 * @since 6.0.0
 */
class GqlHelper
{
    /**
     * @var WeakMap<ElementInterface, array<string, true>>|null
     */
    private static ?WeakMap $visibleFieldHandles = null;

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
     * Returns the user an authenticated schema was created for, if any.
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function getSchemaUser(?GqlSchema $schema = null): ?User
    {
        if ($schema !== null) {
            return $schema->user;
        }

        try {
            return app(GqlService::class)->getActiveSchema()->user;
        } catch (GqlException) {
            return null;
        }
    }

    /**
     * Returns whether a custom field should be included in an authenticated schema’s types, based on whether the user
     * meets its (and its tab’s) user conditions.
     *
     * Element conditions and `showInForm` listeners are evaluated at resolve time instead, via
     * [[guardContentFieldDefinition()]], since listeners may expect an element.
     *
     * @internal
     */
    public static function isFieldVisibleToUser(FieldInterface $field, User $user): bool
    {
        $layoutElement = $field->layoutElement;

        if (! isset($layoutElement->uid)) {
            return true;
        }

        $tab = self::layoutElementTab($layoutElement);

        if ($tab && ! self::meetsUserCondition($tab->getUserCondition(), $user)) {
            return false;
        }

        return self::meetsUserCondition($layoutElement->getUserCondition(), $user);
    }

    /**
     * Returns whether a custom field should be included in an authenticated schema’s mutation arguments, based on
     * whether the user meets its (and its tab’s) user and edit conditions.
     *
     * Element conditions are evaluated at resolve time instead, via [[populateFieldValues()]].
     *
     * @internal
     */
    public static function isFieldEditableByUser(FieldInterface $field, User $user): bool
    {
        if (! self::isFieldVisibleToUser($field, $user)) {
            return false;
        }

        if (! self::meetsUserCondition($field->layoutElement?->getEditCondition(), $user)) {
            return false;
        }

        // Content Block input types would be empty if none of their nested fields are editable
        if ($field instanceof ContentBlockField) {
            return ! empty(self::filterMutableFields($field->getFieldLayout()->getCustomFields()));
        }

        return true;
    }

    /**
     * Filters custom fields down to the ones that should be included in mutation arguments.
     *
     * Fields are only filtered for schemas authenticated as a user.
     *
     * @template T of FieldInterface
     *
     * @param  T[]  $fields
     * @return T[]
     *
     * @internal
     */
    public static function filterMutableFields(array $fields): array
    {
        $user = self::getSchemaUser();

        if (! $user) {
            return $fields;
        }

        return array_values(array_filter($fields, fn (FieldInterface $field) => self::isFieldEditableByUser($field, $user)));
    }

    /**
     * Wraps a content field’s GraphQL definition so it resolves to `null` for elements it isn’t visible for.
     *
     * The definition is returned as-is if the field’s visibility can’t vary by element.
     *
     * @internal
     */
    public static function guardContentFieldDefinition(FieldInterface $field, mixed $definition): mixed
    {
        if (! self::visibilityVariesByElement($field)) {
            return $definition;
        }

        if ($definition instanceof Type) {
            $definition = [
                'name' => $field->handle,
                'type' => $definition,
            ];
        } elseif (! is_array($definition)) {
            return $definition;
        }

        // The field may resolve to null for some elements, so it can’t be non-null
        if (($definition['type'] ?? null) instanceof NonNull) {
            $definition['type'] = $definition['type']->getWrappedType();
        }

        $handle = $field->handle;
        $resolve = $definition['resolve'] ?? null;

        $definition['resolve'] = function (mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo) use ($handle, $resolve): mixed {
            if ($source instanceof ElementInterface && ! self::isFieldVisibleForElement($source, $handle)) {
                return null;
            }

            // Fall back to the parent type’s resolver, so directives are still applied
            $resolve ??= $resolveInfo->parentType->resolveFieldFn ?? Executor::getDefaultFieldResolver();

            return $resolve($source, $arguments, $context, $resolveInfo);
        };

        return $definition;
    }

    /**
     * Sets custom field values on an element for a schema authenticated as a user, like
     * [[ElementInterface::setFieldValuesFromRequest()]]: only fields that are visible and editable for the element are
     * set, and an error is thrown if values were provided for any other fields in its layout.
     *
     * @param  array<string, mixed>  $values  The field values, indexed by field handle
     *
     * @throws Error if values were provided for fields the user can’t edit
     *
     * @internal
     */
    public static function populateFieldValues(ElementInterface $element, array $values): void
    {
        $lockedHandles = ElementHelper::setEditableFieldValuesFromRequest($element, $values);

        if (! empty($lockedHandles)) {
            throw new Error(sprintf(
                'You don’t have permission to edit the following fields: %s',
                implode(', ', $lockedHandles),
            ));
        }
    }

    private static function meetsUserCondition(?UserCondition $condition, User $user): bool
    {
        return ! $condition || $condition->matchElement($user);
    }

    /**
     * Returns the tab that contains a layout element, if it can be conditional.
     */
    private static function layoutElementTab(FieldLayoutElement $layoutElement): ?FieldLayoutTab
    {
        foreach ($layoutElement->getLayout()?->getTabs() ?? [] as $tab) {
            if (! isset($tab->uid)) {
                continue;
            }

            foreach ($tab->getElements() as $tabElement) {
                if ($tabElement === $layoutElement || ($tabElement->uid ?? null) === $layoutElement->uid) {
                    return $tab;
                }
            }
        }

        return null;
    }

    /**
     * Returns whether a custom field’s visibility can vary by element.
     */
    private static function visibilityVariesByElement(FieldInterface $field): bool
    {
        $layoutElement = $field->layoutElement;

        if (! isset($layoutElement->uid)) {
            return false;
        }

        if (Event::hasListeners(FieldLayoutComponentShowInFormResolving::class) || $layoutElement->getElementCondition()) {
            return true;
        }

        return (bool) self::layoutElementTab($layoutElement)?->getElementCondition();
    }

    private static function isFieldVisibleForElement(ElementInterface $element, string $handle): bool
    {
        self::$visibleFieldHandles ??= new WeakMap;

        if (! isset(self::$visibleFieldHandles[$element])) {
            $handles = [];

            foreach ($element->getFieldLayout()?->getVisibleCustomFields($element) ?? [] as $field) {
                $handles[$field->handle] = true;
            }

            self::$visibleFieldHandles[$element] = $handles;
        }

        return isset(self::$visibleFieldHandles[$element][$handle]);
    }

    /**
     * @param  string  $action  The action for which the entities should be extracted. Defaults to "read".
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    /** @return array<string, list<string>> */
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
    /** @return list<string> */
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
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryAllUsers(?GqlSchema $schema = null): bool
    {
        return self::canSchema('usergroups.everyone', schema: $schema);
    }

    /**
     * @param  string  $typeName  The union type name.
     * @param  list<ObjectType>  $includedTypes  The types the union should include
     * @param  callable|null  $resolveFunction  The resolver function to use to resolve a specific type. If not provided,
     *                                          a default one will be used that is able to resolve Craft elements.
     */
    public static function getUnionType(string $typeName, array $includedTypes, ?callable $resolveFunction = null): UnionType
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

            $arguments = Values::getArgumentValues($directiveEntity, $directive, $resolveInfo->variableValues, $resolveInfo->schema);

            $value = $directiveEntity::apply($source, $value, $arguments, $resolveInfo);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>|string
     */
    public static function prepareTransformArguments(array $arguments): array|string
    {
        $handle = $arguments['handle'] ?? null;

        if ($handle === null) {
            return $arguments;
        }

        unset($arguments['handle']);
        $arguments['transform'] = $handle;

        return $arguments;
    }

    /**
     * @param  array<string, mixed>|string  $definition
     */
    public static function resolveAssetTransform(
        Asset $asset,
        array|string $definition,
        string $field,
    ): mixed {
        $transformer = is_array($definition) ? Arr::pull($definition, 'transformer') : null;

        if ($transformer !== null && ! is_string($transformer)) {
            throw new InvalidArgumentException('The transform driver name must be a string.');
        }

        return match ($field) {
            'format' => File::getExtensionByMimeType($asset->transform($definition, $transformer)->mimeType),
            'height' => $asset->transform($definition, $transformer)->height,
            'mimeType' => $asset->transform($definition, $transformer)->mimeType,
            'url' => $asset->transform($definition, $transformer)->url,
            'width' => $asset->transform($definition, $transformer)->width,
            default => throw new InvalidArgumentException("Unsupported transformed Asset field [{$field}]."),
        };
    }

    public static function isAssetTransformField(string $field): bool
    {
        return in_array($field, ['format', 'height', 'mimeType', 'url', 'width'], true);
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
     * Returns whether the schema can query releases, which requires that it can also query drafts.
     *
     * @param  GqlSchema|null  $schema  The GraphQL schema. If none is provided, the active schema will be used.
     */
    public static function canQueryReleases(?GqlSchema $schema = null): bool
    {
        if (! Edition::get()->supportsReleases()) {
            return false;
        }

        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['elements']) && is_array($allowedEntities['elements']) && in_array('drafts', $allowedEntities['elements'], true) && in_array('releases', $allowedEntities['elements'], true);
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

    /** @param array{conditionBuilder?: ElementQueryConditionBuilder}|null $context */
    public static function getFieldNameWithAlias(ResolveInfo $resolveInfo, mixed $source, ?array $context): string
    {
        // $resolveInfo->path is either an array or not set, so we need to check if it's set
        $fieldName = isset($resolveInfo->path) ? array_slice($resolveInfo->path, -1)[0] : $resolveInfo->fieldName;
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

    public static function isMutation(string $query, ?string $operationName = null): bool
    {
        try {
            $document = Parser::parse($query);
        } catch (Error) {
            return false;
        }

        return AST::getOperationAST($document, $operationName)?->operation === 'mutation';
    }
}
