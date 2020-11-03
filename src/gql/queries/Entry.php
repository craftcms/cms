<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEntries()) {
            return [];
        }

        return [
            'entries' => [
                'type' => Type::listOf(EntryInterface::getType()),
                'args' => EntryArguments::getArguments(),
                'resolve' => EntryResolver::class . '::resolve',
                'description' => 'This query is used to query for entries.'
            ],
            'entryCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => EntryArguments::getArguments(),
                'resolve' => EntryResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of entries.'
            ],
            'entry' => [
                'type' => EntryInterface::getType(),
                'args' => EntryArguments::getArguments(),
                'resolve' => EntryResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single entry.'
            ],
        ];
    }
}
