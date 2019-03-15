<?php
namespace craft\gql\queries;

use Craft;
use GraphQL\Type\Definition\Type;
use \craft\gql\types\SiteGroup as SiteGroupType;

/**
 * Class SiteGroup
 */
class SiteGroup
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'querySiteGroup' => [
                'type' => SiteGroupType::getType(),
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
            'queryAllSiteGroups'  => [
                'type' => Type::listOf(SiteGroupType::getType()),
                'resolve' => function () {
                    return Craft::$app->getSites()->getAllGroups();
                },
            ],
        ];
    }
}