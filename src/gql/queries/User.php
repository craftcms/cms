<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryUsers()) {
            return [];
        }

        return [
            'users' => [
                'type' => Type::listOf(UserInterface::getType()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class . '::resolve',
                'description' => 'This query is used to query for users.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'userCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of users.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'user' => [
                'type' => UserInterface::getType(),
                'args' => UserArguments::getArguments(),
                'resolve' => UserResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single user.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
        ];
    }
}
