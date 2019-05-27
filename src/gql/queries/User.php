<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 */
class User
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        // inheritance. base element query shares all that jazz.
        return [
            'queryUsers' => [
                'type' => Type::listOf(UserInterface::getType()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class . '::resolve',
            ],
        ];
    }
}