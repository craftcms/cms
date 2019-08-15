<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 */
class Asset extends BaseQuery
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryAssets()) {
            return [];
        }

        return [
            'queryAssets' => [
                'type' => Type::listOf(AssetInterface::getType()),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class . '::resolve',
                'description' => 'This query is used to query for assets.'
            ],
        ];
    }
}