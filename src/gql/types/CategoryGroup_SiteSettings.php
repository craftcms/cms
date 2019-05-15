<?php
namespace craft\gql\types;

use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class CategoryGroup_SiteSettings
 */
class CategoryGroup_SiteSettings extends BaseSiteSettings
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'CategoryGroup_SiteSettings',
            'fields' => self::class . '::getFields',
        ]));
    }

    public static function getFields(): array
    {
        return array_merge(self::getCommonFields(), [
            'categoryGroup' => CategoryGroup::getType()
        ]);
    }
}
