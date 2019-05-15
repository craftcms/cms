<?php
namespace craft\gql\types;

use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Section_SiteSettings
 */
class Section_SiteSettings extends BaseSiteSettings
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'Section_SiteSettings',
            'fields' => function () {
                return array_merge(self::getSiteSettingFields(), [
                    'section' => Section::getType()
                ]);
            },
        ]));
    }
}
