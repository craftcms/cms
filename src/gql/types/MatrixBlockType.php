<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class MatrixBlockType
 */
class MatrixBlockType extends SchemaObject
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'MatrixBlockType',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'name' => Type::nonNull(Type::string()),
                    'handle' => Type::string(),
                    'sortOrder' => Type::int(),
                    'layout' => [
                        'name' => 'blockTypeFields',
                        'type' => Type::listOf(Field::getType()),
                        'resolve' => function ($value) {
                            /** @var \craft\models\MatrixBlockType $value */
                            return $value->getFields();
                        }
                    ],
                ]);
            },
        ]));
    }
}
