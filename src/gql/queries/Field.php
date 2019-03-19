<?php
namespace craft\gql\queries;

use Craft;
use GraphQL\Type\Definition\Type;
use \craft\gql\types\SiteGroup as SiteGroupType;
use craft\gql\interfaces\Field as FieldInterface;

/**
 * Class Field
 */
class Field
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'queryField' => [
                'type' => FieldInterface::getType(),
                'args' => [
                    'id' => Type::id(),
                    'uid' => Type::string(),
                    'handle' => Type::string(),
                ],
                'resolve' => function ($rootValue, $args) {
                    if (isset($args['uid'])) {
                        return Craft::$app->getFields()->getFieldByUid($args['uid']);
                    }

                    if (isset($args['id'])) {
                        return Craft::$app->getFields()->getFieldById($args['id']);
                    }

                    if (isset($args['handle'])) {
                        return Craft::$app->getFields()->getFieldByHandle($args['handle']);
                    }
                },
            ],
            'queryAllFields'  => [
                'type' => Type::listOf(FieldInterface::getType()),
                'resolve' => function () {
                    return Craft::$app->getFields()->getAllFields();
                },
            ],
        ];
    }
}