<?php
namespace craft\gql\base;

/**
 * Class InterfaceType
 */
abstract class InterfaceType
{
    // Traits
    // =========================================================================

    use GqlTypeTrait;

    // Public methods
    // =========================================================================

    /**
     * Returns the schema object name
     *
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * Returns the associated type generator class.
     *
     * @return string
     */
    abstract public static function getTypeGenerator(): string;
}
