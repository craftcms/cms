<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Resolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Resolver
{
    /**
     * Resolve a field to its value.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     * @return mixed
     */
    abstract public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed;
}
