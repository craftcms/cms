<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Gql\Data\GqlSchema;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * A base for field types that relate elements to the elements of an Eloquent element type.
 *
 * The field type takes its name and icon from the element type. Return it from
 * {@see EloquentElement::relationFieldType()} to register it along with the element type.
 *
 * ```php
 * class CustomersField extends EloquentElementsField
 * {
 *     public static function elementType(): string
 *     {
 *         return Customer::class;
 *     }
 * }
 * ```
 *
 * @since 6.0.0
 */
abstract class EloquentElementsField extends BaseRelationField
{
    /**
     * @return class-string<EloquentElement<covariant Model>>
     */
    #[Override]
    abstract public static function elementType(): string;

    #[Override]
    public static function displayName(): string
    {
        return static::elementType()::pluralDisplayName();
    }

    #[Override]
    public static function icon(): string
    {
        return static::elementType()::cpNavIcon();
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', EloquentElementQuery::class, ElementCollection::class, static::elementType());
    }

    /**
     * Eloquent element types have no GraphQL types to return.
     */
    #[Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return false;
    }
}
