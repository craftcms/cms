<?php
namespace craft\gql\directives;

use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class BaseDirective
 */
abstract class BaseDirective extends Directive
{
    /**
     * Return an instance of the directive
     *
     * @return Directive $directive
     */
    abstract public static function getDirective(): Directive;

    /**
     * Return the directive's name
     *
     * @return string $name
     */
    abstract public static function getName(): string;

    /**
     * Apply the directive to the value with arguments
     *
     * @param mixed $source The original source from which the value was resolved
     * @param mixed $value The value that was resolved
     * @param array $arguments for the directive
     * @param ResolveInfo $resolveInfo resolve info object
     */
    abstract public static function applyDirective($source, $value, array $arguments, ResolveInfo $resolveInfo);
}
