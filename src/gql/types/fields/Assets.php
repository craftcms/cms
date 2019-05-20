<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Assets
 */
class Assets extends BaseRelationField
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
        return 'AssetsField';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        return array_merge(parent::getCommonFields(), [
            'useSingleFolder' => Type::nonNull(Type::boolean()),
            'defaultUploadLocationSource' => Type::string(),
            'defaultUploadLocationSubpath' => Type::string(),
            'singleUploadLocationSource' => Type::string(),
            'singleUploadLocationSubpath' => Type::string(),
            'restrictFiles' => Type::boolean(),
            'allowedKinds' => Type::listOf(Type::string()),
        ]);
    }
}
