<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Gql\Interfaces\Element as ElementGqlType;
use GraphQL\Type\Definition\Type;
use ReflectionClass;

/**
 * HasGraphQlType provides GraphQL type definition functionality.
 *
 * This trait contains methods for defining GraphQL types and scopes
 * for elements exposed through the GraphQL API.
 *
 * @internal
 */
trait HasGqlType
{
    public static function baseGqlType(): Type
    {
        return ElementGqlType::getType();
    }

    /**
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        // Default to no scopes required
        return [];
    }

    /**
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        // Default to the short class name
        return new ReflectionClass($this)->getShortName();
    }
}
