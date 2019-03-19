<?php
namespace craft\gql\types\fields;

use \craft\fields\PlainText as PlainTextField;
use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class UnsupportedField
 */
class UnsupportedField extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'UnsupportedField',
            'fields' => function () {
                return self::getBaseFields();
            },
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }
}
