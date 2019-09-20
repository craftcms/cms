<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use GraphQL\Type\Definition\Type;

/**
 * Class ElementArguments
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class ElementArguments extends Arguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'status' => [
                'name' => 'status',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ statuses.'
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results to only elements that have been archived.'
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results to only elements that have been soft-deleted.'
            ],
            'site' => [
                'name' => 'site',
                'type' => Type::listOf(Type::string()),
                'description' => 'Determines which site(s) the elements should be queried in. Defaults to the primary site.'
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::string(),
                'description' => 'Determines which site(s) the elements should be queried in. Defaults to the primary site.'
            ],
            'unique' => [
                'name' => 'unique',
                'type' => Type::boolean(),
                'description' => 'Determines whether only elements with unique IDs should be returned by the query.'
            ],
            'enabledForSite' => [
                'name' => 'enabledForSite',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on whether the elements are enabled in the site they’re being queried in, per the `site` argument.'
            ],
            'title' => [
                'name' => 'title',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ titles.'
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ slugs.'
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ URIs.'
            ],
            'search' => [
                'name' => 'search',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only elements that match a search query.'
            ],
            'ref' => [
                'name' => 'ref',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on a reference string.'
            ],
            'fixedOrder' => [
                'name' => 'fixedOrder',
                'type' => Type::boolean(),
                'description' => 'Causes the query results to be returned in the order specified by the `id` argument.'
            ],
            'inReverse' => [
                'name' => 'inReverse',
                'type' => Type::boolean(),
                'description' => 'Causes the query results to be returned in reverse order.'
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the elements’ creation dates.'
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the elements’ last-updated dates.'
            ],
            'offset' => [
                'name' => 'offset',
                'type' => Type::int(),
                'description' => 'Sets the offset for paginated results.'
            ],
            'limit' => [
                'name' => 'limit',
                'type' => Type::int(),
                'description' => 'Sets the limit for paginated results.'
            ],
            'orderBy' => [
                'name' => 'orderBy',
                'type' => Type::string(),
                'description' => 'Sets the field the returned elements should be ordered by'
            ],
        ]);
    }
}
