<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 */
class Entry
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        if (!GqlHelper::canQueryEntries()) {
            return [];
        }

        return [
            'queryEntries' => [
                'type' => Type::listOf(EntryInterface::getType()),
                'args' => EntryArguments::getArguments(),
                'resolve' => EntryResolver::class . '::resolve',
            ],
        ];
    }
}