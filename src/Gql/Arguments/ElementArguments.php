<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Gql\Attributes\Gql;
use CraftCms\Cms\Gql\Events\GqlArgumentsResolving;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\Input\Criteria\AssetRelation;
use CraftCms\Cms\Gql\Types\Input\Criteria\EntryRelation;
use CraftCms\Cms\Gql\Types\Input\Criteria\UserRelation;
use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
abstract class ElementArguments extends Arguments
{
    /** @return array<string, ArgumentConfig> */
    #[\Override]
    public static function getArguments(): array
    {
        $arguments = array_merge(
            parent::getArguments(),
            static::gqlQueryParamArguments(static::elementType() ?? Element::class),
            [
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
            ],
            [
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
            ],
        );

        $arguments['relatedTo']['description'] .= ' This argument is ignored, if `relatedToAll` is also used.';

        event($event = new GqlArgumentsResolving(
            arguments: $arguments,
            argumentClass: static::class,
        ));

        return $event->arguments;
    }

    /**
     * Returns the element type whose query params should be added as arguments automatically.
     *
     * Every param that the element type’s query declares with {@see ElementQueryParam} and doesn’t exclude
     * with {@see Gql} is added, if it applies to the element type and its gate allows it. Without an element type, the base
     * element query’s params that apply to every element type are added, if their gates allow the base element type.
     *
     * @return class-string<ElementInterface>|null
     */
    protected static function elementType(): ?string
    {
        return null;
    }
}
