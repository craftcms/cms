<?php
namespace craft\gql\interfaces;

use craft\base\ElementInterface;
use craft\gql\common\SchemaObject;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\types\generators\ElementType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Element
 */
abstract class BaseInterface extends SchemaObject
{
    /**
     * Returns the associated type generator class.
     *
     * @return string
     */
    abstract public static function getTypeGenerator(): string;
}
