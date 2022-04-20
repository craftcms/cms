<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Directive
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Directive extends GqlDirective
{
    /**
     * Return an instance of the directive
     *
     * @return GqlDirective $directive
     */
    abstract public static function create(): GqlDirective;

    /**
     * Return the directive's name
     *
     * @return string $name
     */
    abstract public static function name(): string;

    /**
     * Apply the directive to the value with arguments
     *
     * @param mixed $source The original source from which the value was resolved
     * @param mixed $value The value that was resolved
     * @param array $arguments for the directive
     * @param ResolveInfo $resolveInfo resolve info object
     * @return mixed
     */
    abstract public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed;
}
