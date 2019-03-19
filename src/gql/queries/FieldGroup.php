<?php
namespace craft\gql\queries;

use Craft;
use craft\gql\types\FieldGroup as FieldGroupType;
use GraphQL\Type\Definition\Type;

/**
 * Class FieldGroup
 */
class FieldGroup
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'queryFieldGroup' => [
                'type' => FieldGroupType::getType(),
                'args' => [
                    'id' => Type::id(),
                    'uid' => Type::string(),
                ],
                'resolve' => function ($rootValue, $args) {
                    if (isset($args['uid'])) {
                        return Craft::$app->getFields()->getGroupByUid($args['uid']);
                    }

                    if (isset($args['id'])) {
                        return Craft::$app->getFields()->getGroupById($args['id']);
                    }
                },
            ],
            'queryAllFieldGroups'  => [
                'type' => Type::listOf(FieldGroupType::getType()),
                'resolve' => function () {
                    return Craft::$app->getFields()->getAllGroups();
                },
            ],
        ];
    }
}