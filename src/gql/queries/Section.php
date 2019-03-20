<?php
namespace craft\gql\queries;

use Craft;
use GraphQL\Type\Definition\Type;
use craft\gql\types\Section as SectionType;

/**
 * Class Section
 */
class Section
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'querySection' => [
                'type' => SectionType::getType(),
                'args' => [
                    'id' => Type::id(),
                    'uid' => Type::string(),
                    'handle' => Type::string(),
                ],
                'resolve' => function ($rootValue, $args) {
                    if (isset($args['uid'])) {
                        return Craft::$app->getSections()->getSectionByUid($args['uid']);
                    }

                    if (isset($args['id'])) {
                        return Craft::$app->getSections()->getSectionById($args['id']);
                    }

                    if (isset($args['handle'])) {
                        return Craft::$app->getSections()->getSectionByHandle($args['handle']);
                    }
                },
            ],
            'queryAllSections'  => [
                'type' => Type::listOf(SectionType::getType()),
                'resolve' => function () {
                    return Craft::$app->getSections()->getAllSections();
                },
            ],
        ];
    }
}