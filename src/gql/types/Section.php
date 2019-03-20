<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\models\Section as SectionModel;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Structure
 */
class Section extends SchemaObject
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'Section',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'structure' => Structure::getType(),
                    'name' => Type::string(),
                    'handle' => Type::string(),
                    'type' => Type::nonNull(new EnumType([
                        'name' => 'sectionType',
                        'values' => [
                            'single',
                            'channel',
                            'structure',
                        ],
                    ])),
                    'enableVersioning' => Type::nonNull(Type::boolean()),
                    'propagateEntries' => Type::nonNull(Type::string()),
                    'siteSettings' => [
                        'name' => 'siteSettings',
                        'type' => Type::listOf(Section_SiteSettings::getType()),
                        'resolve' => function(SectionModel $section) {
                            return $section->getSiteSettings();
                        }
                    ],
                ]);
            },
        ]));
    }
}
