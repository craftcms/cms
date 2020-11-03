<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\gql\types\QueryArgument;
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
        return array_merge(parent::getArguments(), static::getDraftArguments(), [
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
                'description' => 'Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.'
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::string(),
                'description' => 'Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.'
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
            'relatedTo' => [
                'name' => 'relatedTo',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results to elements that relate to *any* of the provided element IDs. This argument is ignored, if `relatedToAll` is also used.'
            ],
            'relatedToAll' => [
                'name' => 'relatedToAll',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results to elements that relate to *all* of the provided element IDs. Using this argument will cause `relatedTo` argument to be ignored.'
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
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ creation dates.'
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => Type::listOf(Type::string()),
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

    /**
     * Return the arguments used for elements that support drafts.
     *
     * @return array
     */
    public static function getDraftArguments(): array
    {
        return [
            'drafts' => [
                'name' => 'drafts',
                'type' => Type::boolean(),
                'description' => 'Whether draft elements should be returned.',
            ],
            'draftOf' => [
                'name' => 'draftOf',
                'type' => QueryArgument::getType(),
                'description' => 'The source element ID that drafts should be returned for. Set to `false` to fetch unsaved drafts.',
            ],
            'draftId' => [
                'name' => 'draftId',
                'type' => Type::int(),
                'description' => 'The ID of the draft to return (from the `drafts` table)',
            ],
            'draftCreator' => [
                'name' => 'draftCreator',
                'type' => Type::int(),
                'description' => 'The drafts’ creator ID',
            ],
            'revisions' => [
                'name' => 'revisions',
                'type' => Type::boolean(),
                'description' => 'Whether revision elements should be returned.',
            ],
            'revisionOf' => [
                'name' => 'revisionOf',
                'type' => QueryArgument::getType(),
                'description' => 'The source element ID that revisions should be returned for',
            ],
            'revisionId' => [
                'name' => 'revisionId',
                'type' => Type::int(),
                'description' => 'The ID of the revision to return (from the `revisions` table)',
            ],
            'revisionCreator' => [
                'name' => 'revisionCreator',
                'type' => Type::int(),
                'description' => 'The revisions’ creator ID',
            ],
        ];
    }
}
