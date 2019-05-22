<?php
namespace craft\gql\resolvers;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class BaseResolver
 */
abstract class BaseResolver
{
    /**
     * Resolve a field to its value.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     */
    abstract public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo);
}
