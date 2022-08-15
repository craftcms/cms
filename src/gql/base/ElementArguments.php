<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\gql\types\input\criteria\Asset;
use craft\gql\types\input\criteria\Category;
use craft\gql\types\input\criteria\Entry;
use craft\gql\types\input\criteria\Tag;
use craft\gql\types\input\criteria\User;
use craft\gql\types\QueryArgument;
use craft\helpers\Gql;
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
        return array_merge(parent::getArguments(), static::getDraftArguments(), static::getRevisionArguments(), static::getStatusArguments(), [
            'site' => [
                'name' => 'site',
                'type' => Type::listOf(Type::string()),
                'description' => 'Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.',
            ],
            'unique' => [
                'name' => 'unique',
                'type' => Type::boolean(),
                'description' => 'Determines whether only elements with unique IDs should be returned by the query.',
            ],
            'preferSites' => [
                'name' => 'preferSites',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Determines which site should be selected when querying multi-site elements.',
            ],
            'title' => [
                'name' => 'title',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ titles.',
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ slugs.',
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ URIs.',
            ],
            'search' => [
                'name' => 'search',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only elements that match a search query.',
            ],
            'relatedTo' => [
                'name' => 'relatedTo',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results to elements that relate to the provided element IDs. This argument is ignored, if `relatedToAll` is also used.',
            ],
            'relatedToAssets' => [
                'name' => 'relatedToAssets',
                'type' => Type::listOf(Asset::getType()),
                'description' => 'Narrows the query results to elements that relate to an asset list defined with this argument.',
            ],
            'relatedToEntries' => [
                'name' => 'relatedToEntries',
                'type' => Type::listOf(Entry::getType()),
                'description' => 'Narrows the query results to elements that relate to an entry list defined with this argument.',
            ],
            'relatedToUsers' => [
                'name' => 'relatedToUsers',
                'type' => Type::listOf(User::getType()),
                'description' => 'Narrows the query results to elements that relate to a use list defined with this argument.',
            ],
            'relatedToCategories' => [
                'name' => 'relatedToCategories',
                'type' => Type::listOf(Category::getType()),
                'description' => 'Narrows the query results to elements that relate to a category list defined with this argument.',
            ],
            'relatedToTags' => [
                'name' => 'relatedToTags',
                'type' => Type::listOf(Tag::getType()),
                'description' => 'Narrows the query results to elements that relate to a tag list defined with this argument.',
            ],
            'relatedToAll' => [
                'name' => 'relatedToAll',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results to elements that relate to *all* of the provided element IDs. Using this argument will cause `relatedTo` argument to be ignored. **This argument is deprecated.** `relatedTo: ["and", ...ids]` should be used instead.',
            ],
            'ref' => [
                'name' => 'ref',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on a reference string.',
            ],
            'fixedOrder' => [
                'name' => 'fixedOrder',
                'type' => Type::boolean(),
                'description' => 'Causes the query results to be returned in the order specified by the `id` argument.',
            ],
            'inReverse' => [
                'name' => 'inReverse',
                'type' => Type::boolean(),
                'description' => 'Causes the query results to be returned in reverse order.',
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ creation dates.',
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ last-updated dates.',
            ],
            'offset' => [
                'name' => 'offset',
                'type' => Type::int(),
                'description' => 'Sets the offset for paginated results.',
            ],
            'limit' => [
                'name' => 'limit',
                'type' => Type::int(),
                'description' => 'Sets the limit for paginated results.',
            ],
            'orderBy' => [
                'name' => 'orderBy',
                'type' => Type::string(),
                'description' => 'Sets the field the returned elements should be ordered by.',
            ],
            'siteSettingsId' => [
                'name' => 'siteSettingsId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the unique identifier for an element-site relation.',
            ],
        ]);
    }

    /**
     * Return the various status arguments.
     *
     * @return array
     * @since 3.6.7
     */
    public static function getStatusArguments(): array
    {
        if (!Gql::canQueryInactiveElements()) {
            return [];
        }

        return [
            'status' => [
                'name' => 'status',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ statuses.',
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results to only elements that have been archived.',
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results to only elements that have been soft-deleted.',
            ],
        ];
    }

    /**
     * Return the arguments used for elements that support drafts.
     *
     * @return array
     */
    public static function getDraftArguments(): array
    {
        if (!Gql::canQueryDrafts()) {
            return [];
        }

        return [
            'drafts' => [
                'name' => 'drafts',
                'type' => Type::boolean(),
                'description' => 'Whether draft elements should be returned.',
            ],
            'draftOf' => [
                'name' => 'draftOf',
                'type' => QueryArgument::getType(),
                'description' => 'Narrows the query results to only drafts of a given element.  Set to `false` to fetch unpublished drafts.',
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
            'provisionalDrafts' => [
                'name' => 'provisionalDrafts',
                'type' => Type::boolean(),
                'description' => 'Whether provisional drafts should be returned.',
            ],
        ];
    }

    /**
     * Return the arguments used for elements that support revisions.
     *
     * @return array
     * @since 3.6.8
     */
    public static function getRevisionArguments(): array
    {
        if (!Gql::canQueryRevisions()) {
            return [];
        }

        return [
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
