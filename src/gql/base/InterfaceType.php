<?php
namespace craft\gql\base;

/**
 * Class InterfaceType
 */
abstract class InterfaceType extends SchemaObject
{
    /**
     * Returns the associated type generator class.
     *
     * @return string
     */
    abstract public static function getTypeGenerator(): string;
}
