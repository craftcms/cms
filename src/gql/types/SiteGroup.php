<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\models\SiteGroup as SiteGroupModel;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class SiteGroup
 */
class SiteGroup extends SchemaObject
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'SiteGroup',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'name' => Type::nonNull(Type::string()),
                    'sites' => [
                        'name' => 'sites',
                        'type' => Type::listOf(Site::getType()),
                        'resolve' => function(SiteGroupModel $siteGroup) {
                            return $siteGroup->getSites();
                        }
                    ]
                ]);
            },
        ]));
    }
}
