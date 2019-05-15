<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class FieldGroup
 */
class FieldGroup extends SchemaObject
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
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
