<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Section_SiteSettings
 */
class Section_SiteSettings extends BaseSiteSettings
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'Section_SiteSettings',
            'fields' => function () {
                return array_merge(self::getSiteSettingFields(), [
                    'section' => Section::getType()
                ]);
            },
        ]));
    }
}
