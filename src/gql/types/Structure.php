<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Structure
 */
class Structure extends BaseType
{
    public static function getType(): ObjectType
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
