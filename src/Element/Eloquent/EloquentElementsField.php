<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Gql\Arguments\Elements\EloquentElement as EloquentElementArguments;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\Elements\EloquentElement as EloquentElementResolver;
use CraftCms\Cms\Gql\Types\Generators\EloquentElementType;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;
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
 * @phpstan-import-type ArgumentConfig from Argument
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
        return sprintf('\\%1$s<\\%3$s>|\\%2$s<int, \\%3$s>', EloquentElementQuery::class, ElementCollection::class, static::elementType());
    }

    /**
     * Included in schemas that can query the element type.
     */
    #[Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return GqlHelper::canSchema(static::elementType()::gqlScope(), schema: $schema);
    }

    /**
     * @return array{
     *     name: string,
     *     type: Type,
     *     args: array<string, ArgumentConfig>,
     *     resolve: string,
     *     complexity: callable,
     * }
     */
    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(EloquentElementType::generateType(static::elementType()))),
            'args' => EloquentElementArguments::forElementType(static::elementType()),
            'resolve' => EloquentElementResolver::class.'::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }
}
