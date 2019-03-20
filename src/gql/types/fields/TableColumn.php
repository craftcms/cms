<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use craft\helpers\Json;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class TableColumn
 */
class TableColumn extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'TableColumn',
            'fields' => [
                'key' => Type::string(),
                'heading' => Type::string(),
                'handle' => Type::string(),
                'width' => Type::string(),
                'type' => Type::string(),
            ],
        ]));
    }
}
