<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Gql\Arguments\Elements\User as UserArguments;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\User as UserResolver;
use GraphQL\Type\Definition\Type;

class User extends Query
{
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && ! GqlHelper::canQueryUsers()) {
            return [];
        }

        return [
            'users' => [
                'type' => Type::listOf(UserInterface::getType()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class.'::resolve',
                'description' => 'This query is used to query for users.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'userCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class.'::resolveCount',
                'description' => 'This query is used to return the number of users.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'user' => [
                'type' => UserInterface::getType(),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class.'::resolveOne',
                'description' => 'This query is used to query for a single user.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
        ];
    }
}
