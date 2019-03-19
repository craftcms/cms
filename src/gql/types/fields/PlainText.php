<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class PlainText
 */
class PlainText extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'PlainTextField',
            'fields' => function () {
                return array_merge(self::getBaseFields(), [
                    'placeholder' => Type::string(),
                    'code' => Type::nonNull(Type::boolean()),
                    'multiline' => Type::nonNull(Type::boolean()),
                    'initialRows' => Type::nonNull(Type::int()),
                    'charLimit' => Type::int(),
                    'columnType' => Type::nonNull(Type::string()),
                ]);
            },
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }
}
