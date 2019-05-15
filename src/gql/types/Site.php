<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use craft\models\Site as SiteModel;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Site
 */
class Site extends SchemaObject
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'Site',
            'fields' => self::class . '::getFields',
        ]));
    }

    public static function getFields(): array
    {
        return array_merge(parent::getCommonFields(), [
            'siteGroup' => [
                'name' => 'siteGroup',
                'type' => SiteGroup::getType(),
                'resolve' => function(SiteModel $siteModel) {
                    return $siteModel->getGroup();
                }
            ],
            'primary' => Type::boolean(),
            'name' => Type::string(),
            'handle' => Type::string(),
            'language' => Type::string(),
            'baseUrl' => Type::string(),
            'sortOrder' => Type::int(),
            'hasUrls' => Type::boolean(),
        ]);
    }
}
