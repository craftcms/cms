<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class FieldGroup
 */
class FieldGroup extends SchemaObject
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'FieldGroup',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'name' => Type::nonNull(Type::string()),
// TODO fields?
//                    'fields' =>
                ]);
            },
        ]));
    }
}
