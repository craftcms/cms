<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input\Criteria;

use CraftCms\Cms\Gql\Arguments\Elements\Asset as AssetArguments;
use CraftCms\Cms\Gql\Arguments\RelationCriteria;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class AssetRelation extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'AssetRelationCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => fn () => AssetArguments::getArguments() + RelationCriteria::getArguments(),
        ]));
    }
}
