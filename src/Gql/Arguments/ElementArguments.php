<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Gql\Events\GqlArgumentsResolving;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Types\Input\Criteria\AssetRelation;
use CraftCms\Cms\Gql\Types\Input\Criteria\EntryRelation;
use CraftCms\Cms\Gql\Types\Input\Criteria\UserRelation;
use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type ArgumentConfig from Argument */
abstract class ElementArguments extends Arguments
{
    /** @return array<string, ArgumentConfig> */
    #[\Override]
    public static function getArguments(): array
    {
        $arguments = array_merge(parent::getArguments(), static::getDraftArguments(), static::getRevisionArguments(), static::getStatusArguments(), [
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
            'searchTermOptions' => [
                'name' => 'searchTermOptions',
                'type' => GqlEntityRegistry::getOrCreate('SearchTermOptions', fn () => new InputObjectType([
                    'name' => 'SearchTermOptions',
                    'fields' => fn () => [
                        'subLeft' => [
                            'name' => 'subLeft',
                            'type' => Type::boolean(),
                        ],
                        'subRight' => [
                            'name' => 'subRight',
                            'type' => Type::boolean(),
                        ],
                        'exclude' => [
                            'name' => 'exclude',
                            'type' => Type::boolean(),
                        ],
                        'exact' => [
                            'name' => 'exact',
                            'type' => Type::boolean(),
                        ],
                    ],
                ])),
                'description' => 'Defines the default options that should be applied terms within the `search` argument.',
            ],
            'relatedTo' => [
                'name' => 'relatedTo',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results to elements that relate to the provided element IDs. This argument is ignored, if `relatedToAll` is also used.',
            ],
            'notRelatedTo' => [
                'name' => 'notRelatedTo',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results to elements that do not relate to the provided element IDs.',
            ],
            'relatedToAssets' => [
                'name' => 'relatedToAssets',
                // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                'type' => Type::listOf(AssetRelation::getType()),
                'description' => 'Narrows the query results to elements that relate to an asset list defined with this argument.',
            ],
            'relatedToEntries' => [
                'name' => 'relatedToEntries',
                // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                'type' => Type::listOf(EntryRelation::getType()),
                'description' => 'Narrows the query results to elements that relate to an entry list defined with this argument.',
            ],
            'relatedToUsers' => [
                'name' => 'relatedToUsers',
                // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                'type' => Type::listOf(UserRelation::getType()),
                'description' => 'Narrows the query results to elements that relate to a use list defined with this argument.',
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
            'language' => [
                'name' => 'language',
                'type' => Type::listOf(Type::string()),
                'description' => 'Determines which site(s) the elements should be queried in, based on their language.',
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

        event($event = new GqlArgumentsResolving(
            arguments: $arguments,
            argumentClass: static::class,
        ));

        return $event->arguments;
    }

    /** @return array<string, ArgumentConfig> */
    public static function getStatusArguments(): array
    {
        if (! Gql::canQueryInactiveElements()) {
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

    /** @return array<string, ArgumentConfig> */
    public static function getDraftArguments(): array
    {
        if (! Gql::canQueryDrafts()) {
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
            'withProvisionalDrafts' => [
                'name' => 'withProvisionalDrafts',
                'type' => Type::boolean(),
                'description' => 'Whether canonical elements should be replaced with provisional drafts if those exist.',
            ],
        ];
    }

    /** @return array<string, ArgumentConfig> */
    public static function getRevisionArguments(): array
    {
        if (! Gql::canQueryRevisions()) {
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
