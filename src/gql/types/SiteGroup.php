<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
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
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'SiteGroup',
            'fields' => self::class . '::getFields',
        ]));
    }

    public static function getFields(): array
    {
        return array_merge(parent::getCommonFields(), [
            'name' => Type::nonNull(Type::string()),
            'sites' => [
                'name' => 'sites',
                'type' => Type::listOf(Site::getType()),
                'resolve' => function(SiteGroupModel $siteGroup) {
                    return $siteGroup->getSites();
                }
            ]
        ]);
    }
}
