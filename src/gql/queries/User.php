<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 */
class User extends Query
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
            'users' => [
                'type' => Type::listOf(UserInterface::getType()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class . '::resolve',
                'description' => 'This query is used to query for users.'
            ],
        ];
    }
}