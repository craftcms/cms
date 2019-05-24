<?php
namespace craft\gql\directives;

use GraphQL\Type\Definition\Directive;

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
     * @param $value
     * @param array $arguments
     */
    abstract public static function applyDirective($value, array $arguments);
}
