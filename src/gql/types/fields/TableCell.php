<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRow
 */
class TableCell extends BaseField
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'TableCell',
            'fields' => [
                'columnKey' => Type::nonNull(Type::string()),
                'content' => Type::string(),
            ],
        ]));
    }
}
