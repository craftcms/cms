<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class UnsupportedField
 */
class UnsupportedField extends BaseField
{
    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'UnsupportedField';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return parent::getCommonFields();
    }
}
