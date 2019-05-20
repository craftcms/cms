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
    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return array_merge(self::getCommonFields(), [
            'section' => Section::getType()
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string {
        return 'Section_SiteSettings';
    }

}
