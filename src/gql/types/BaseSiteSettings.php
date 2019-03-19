<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseSiteSettings
 */
abstract class BaseSiteSettings extends SchemaObject
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
