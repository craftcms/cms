<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use craft\gql\types\MatrixBlockType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Matrix
 */
class Matrix extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'MatrixField',
            'fields' => function () {
                return array_merge(self::getBaseFields(), [
                    'minBlocks' => Type::int(),
                    'maxBlock' => Type::int(),
                    'contentTable' => Type::string(),
                    'localizeBlocks' => Type::nonNull(Type::boolean()),
                    'blockTypes' => Type::listOf(MatrixBlockType::getType()),
                ]);
            },
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }
}
