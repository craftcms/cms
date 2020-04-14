<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\base\Query;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Asset extends Query
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
            'assets' => [
                'type' => Type::listOf(AssetInterface::getType()),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class . '::resolve',
                'description' => 'This query is used to query for assets.'
            ],
            'assetCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of assets.'
            ],
            'asset' => [
                'type' => AssetInterface::getType(),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single asset.'
            ],
        ];
    }
}
