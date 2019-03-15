<?php
namespace craft\gql\types;

use craft\models\CategoryGroup as CategoryGroupModel;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseSiteSettings
 */
abstract class BaseSiteSettings extends BaseType
{
    protected static function getSiteSettingFields(): array
    {
        return array_merge(self::getCommonFields(), [
            'site' => Site::getType(),
            'hasUrls' => Type::boolean(),
            'uriFormat' => Type::string(),
            'template' => Type::string(),
        ]);
    }
}
