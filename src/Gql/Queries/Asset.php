<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Gql\Arguments\Elements\Asset as AssetArguments;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset as AssetInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\Asset as AssetResolver;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
class Asset extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && ! GqlHelper::canQueryAssets()) {
            return [];
        }

        return [
            'assets' => [
                'type' => Type::listOf(AssetInterface::getType()),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class.'::resolve',
                'description' => 'This query is used to query for assets.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'assetCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class.'::resolveCount',
                'description' => 'This query is used to return the number of assets.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'asset' => [
                'type' => AssetInterface::getType(),
                'args' => AssetArguments::getArguments(),
                'resolve' => AssetResolver::class.'::resolveOne',
                'description' => 'This query is used to query for a single asset.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
        ];
    }
}
