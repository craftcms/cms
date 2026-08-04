<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;

abstract class Directive extends GqlDirective
{
    /**
     * @return GqlDirective $directive
     */
    abstract public static function create(): GqlDirective;

    /**
     * @return string $name
     */
    abstract public static function name(): string;

    /**
     * @param  mixed  $source  The original source from which the value was resolved
     * @param  mixed  $value  The value that was resolved
     * @param  array  $arguments  for the directive
     * @param  ResolveInfo  $resolveInfo  resolve info object
     */
    abstract public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed;
}
