<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Assets
 */
class Assets extends BaseRelationField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'AssetsField',
            'fields' => function () {
                return array_merge(self::getBaseFields(), [
                    'useSingleFolder' => Type::nonNull(Type::boolean()),
                    'defaultUploadLocationSource' => Type::string(),
                    'defaultUploadLocationSubpath' => Type::string(),
                    'singleUploadLocationSource' => Type::string(),
                    'singleUploadLocationSubpath' => Type::string(),
                    'restrictFiles' => Type::boolean(),
                    'allowedKinds' => Type::listOf(Type::string()),
                ]);
            },
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }
}
