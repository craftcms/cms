<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class StructureNode
 */
class StructureNode extends SchemaObject
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'StructureNode',
            'fields' => function () {
                return array_merge(parent::getCommonFields(), [
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
