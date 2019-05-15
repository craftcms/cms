<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use craft\models\CategoryGroup as CategoryGroupModel;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class CategoryGroup
 */
class CategoryGroup extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'CategoryGroup',
            'fields' => self::class . '::getFields',
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return array_merge(parent::getCommonFields(), [
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
    }
}
