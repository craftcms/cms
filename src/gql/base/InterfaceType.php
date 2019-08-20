<?php
namespace craft\gql\base;

use craft\gql\common\SchemaObject;

/**
 * Class Element
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
