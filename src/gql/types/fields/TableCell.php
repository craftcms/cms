<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use craft\helpers\Json;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRow
 */
class TableCell extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'TableCell',
            'fields' => [
                'columnKey' => Type::nonNull(Type::string()),
                'content' => Type::string(),
            ],
        ]));
    }
}
