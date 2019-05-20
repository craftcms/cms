<?php
namespace craft\gql\types\enums;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseEnum
 */
abstract class BaseEnum extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        return TypeRegistry::getType(static::class) ?: TypeRegistry::createType(static::class, new EnumType([
            'name' => static::getName(),
            'values' => static::getFields(),
        ]));
    }
}
