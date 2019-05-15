<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use craft\gql\interfaces\Field;
use craft\gql\types\MatrixBlockType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Matrix
 */
class Matrix extends BaseField
{
    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'MatrixField',
            'fields' => self::class . '::getFields',
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        return array_merge(parent::getCommonFields(), [
            'minBlocks' => Type::int(),
            'maxBlock' => Type::int(),
            'contentTable' => Type::string(),
            'localizeBlocks' => Type::nonNull(Type::boolean()),
            'blockTypes' => Type::listOf(MatrixBlockType::getType()),
        ]);
    }
}
