<?php
namespace craft\gql\types;

use craft\models\Site as SiteModel;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Site
 */
class Site extends BaseType
{
    public static function getType(): ObjectType
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'Site',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
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
            },
        ]));
    }
}
