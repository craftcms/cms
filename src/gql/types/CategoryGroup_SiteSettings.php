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
    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return array_merge(self::getCommonFields(), [
            'categoryGroup' => CategoryGroup::getType()
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'CategoryGroup_SiteSettings';
    }
}
