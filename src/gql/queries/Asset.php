<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 */
class Asset
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        // inheritance. base element query shares all that jazz.
        return [
            'queryAssets' => [
                'type' => Type::listOf(AssetInterface::getType()),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class . '::resolve',
            ],
        ];
    }
}