<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Structure
 */
class Structure extends SchemaObject
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'Structure',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'maxLevels' => Type::int(),
                    'root' => StructureNode::getType(),
                ]);
            },
        ]));
    }
}
