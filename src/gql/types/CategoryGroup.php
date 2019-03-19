<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\models\CategoryGroup as CategoryGroupModel;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class CategoryGroup
 */
class CategoryGroup extends SchemaObject
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'CategoryGroup',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'name' => Type::nonNull(Type::string()),
                    'handle' => Type::nonNull(Type::string()),
                    'siteSettings' => [
                        'name' => 'siteSettings',
                        'type' => Type::listOf(CategoryGroup_SiteSettings::getType()),
                        'resolve' => function(CategoryGroupModel $categoryGroup) {
                            return $categoryGroup->getSiteSettings();
                        }
                    ]
                ]);
            },
        ]));
    }
}
