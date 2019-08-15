<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 */
class User extends BaseQuery
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryUsers()) {
            return [];
        }

        return [
            'queryUsers' => [
                'type' => Type::listOf(UserInterface::getType()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class . '::resolve',
                'description' => 'This query is used to query for users.'
            ],
        ];
    }
}