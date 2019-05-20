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
    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'TableCell';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
            'columnKey' => Type::nonNull(Type::string()),
            'content' => Type::string(),
        ];
    }
}
