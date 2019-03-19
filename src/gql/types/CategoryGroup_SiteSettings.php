<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class CategoryGroup_SiteSettings
 */
class CategoryGroup_SiteSettings extends BaseSiteSettings
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'CategoryGroup_SiteSettings',
            'fields' => function () {
                return array_merge(self::getSiteSettingFields(), [
                    'categoryGroup' => CategoryGroup::getType()
                ]);
            },
        ]));
    }
}
