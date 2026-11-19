<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers;

use BadMethodCallException;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Users as UsersField;
use CraftCms\Cms\Gql\ElementQueryConditionBuilder;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use Override;

/**
 * Resolves the `relatedEntriesBy`, `relatedAssetsBy`, and `relatedUsersBy` element fields, which return the elements
 * of a type that relate to or reference an element through a field.
 *
 * The elements are limited to what the active schema allows, the way the relation field for their type limits them.
 * {@see ElementQueryConditionBuilder} eager-loads them with the same limits.
 *
 * @since 6.0.0
 */
class RelatedBy extends ElementResolver
{
    /**
     * The relation field type whose schema conditions apply to each GraphQL field.
     *
     * @var array<string, class-string<BaseRelationField>>
     */
    public const array FIELD_TYPES = [
        'relatedEntriesBy' => EntriesField::class,
        'relatedAssetsBy' => AssetsField::class,
        'relatedUsersBy' => UsersField::class,
    ];

    /**
     * Returns whether a field stores relations or references that the fields can return the sources of.
     */
    public static function isRelatedByField(mixed $field): bool
    {
        return $field instanceof RelationalFieldInterface || $field instanceof TracksReferencesFieldInterface;
    }

    /** @param array<string, mixed> $arguments */
    #[Override]
    protected static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        if (! $source instanceof ElementInterface) {
            return ElementCollection::empty();
        }

        $eagerLoadedElements = $source->getEagerLoadedElements((string) $fieldName);

        if ($eagerLoadedElements !== null) {
            return $eagerLoadedElements;
        }

        // Not eager-loaded, so the field name isn't an alias
        $fieldType = self::FIELD_TYPES[$fieldName] ?? null;
        $field = Fields::getFieldByHandle((string) Arr::pull($arguments, 'field'));
        $conditions = $fieldType ? (new $fieldType)->getEagerLoadingGqlConditions() : null;

        if ($conditions === null || ! self::isRelatedByField($field)) {
            return ElementCollection::empty();
        }

        $query = Elements::createElementQuery($fieldType::elementType())->siteId($source->siteId);

        foreach (GqlHelper::restrictArgumentsToConditions($arguments, $conditions) as $key => $value) {
            try {
                $query->$key($value);
            } catch (BadMethodCallException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        return $query->andRelatedTo(['targetElement' => $source, 'field' => $field->handle]);
    }
}
