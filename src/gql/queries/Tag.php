<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\Tag as TagArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\resolvers\elements\Tag as TagResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Tag extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryTags()) {
            return [];
        }

        return [
            'tags' => [
                'type' => Type::listOf(TagInterface::getType()),
                'args' => TagArguments::getArguments(),
                'resolve' => TagResolver::class . '::resolve',
                'description' => 'This query is used to query for tags.'
            ],
            'tagCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => TagArguments::getArguments(),
                'resolve' => TagResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of tags.'
            ],
            'tag' => [
                'type' => TagInterface::getType(),
                'args' => TagArguments::getArguments(),
                'resolve' => TagResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single tag.'
            ],
        ];
    }
}
