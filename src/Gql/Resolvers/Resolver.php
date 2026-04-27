<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;

abstract class Resolver
{
    /**
     * @param  mixed  $source  The parent data source to use for resolving this field
     * @param  array  $arguments  arguments for resolving this field.
     * @param  mixed  $context  The context shared between all resolvers
     * @param  ResolveInfo  $resolveInfo  The resolve information
     */
    abstract public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed;
}
