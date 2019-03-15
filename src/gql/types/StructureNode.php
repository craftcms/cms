<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class StructureNode
 */
class StructureNode extends BaseType
{
    public static function getType(): ObjectType
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'StructureNode',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'root' => Type::int(),
                    'lft' => Type::nonNull(Type::int()),
                    'rgt' => Type::nonNull(Type::int()),
                    'level' => Type::nonNull(Type::int()),
                    'element' => Type::string(),
                    'structure' => Structure::getType(),
                ]);
            },
        ]));
    }
}
