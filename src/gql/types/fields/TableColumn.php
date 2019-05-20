<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class TableColumn
 */
class TableColumn extends BaseField
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
        return 'TableColumn';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
            'key' => Type::string(),
            'heading' => Type::string(),
            'handle' => Type::string(),
            'width' => Type::string(),
            'type' => Type::string(),
        ];
    }

}
