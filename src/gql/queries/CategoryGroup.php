<?php
namespace craft\gql\queries;

use Craft;
use craft\gql\types\CategoryGroup as CategoryGroupType;
use GraphQL\Type\Definition\Type;

/**
 * Class CategoryGroup
 */
class CategoryGroup
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'queryCategoryGroup' => [
                'type' => CategoryGroupType::getType(),
                'args' => [
                    'id' => Type::id(),
                    'uid' => Type::string(),
                    'handle' => Type::string(),
                ],
                'resolve' => function ($rootValue, $args) {
                    if (isset($args['uid'])) {
                        return Craft::$app->getCategories()->getGroupById($args['uid']);
                    }

                    if (isset($args['id'])) {
                        return Craft::$app->getCategories()->getGroupByUid($args['id']);
                    }

                    if (isset($args['handle'])) {
                        return Craft::$app->getCategories()->getGroupByHandle($args['handle']);
                    }
                },
            ],
            'queryAllCategoryGroups'  => [
                'type' => Type::listOf(CategoryGroupType::getType()),
                'resolve' => function () {
                    return Craft::$app->getCategories()->getAllGroups();
                },
            ],
        ];
    }
}