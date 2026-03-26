<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input\Criteria;

use CraftCms\Cms\Gql\Arguments\Elements\Asset as AssetArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class Asset extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'AssetCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => AssetArguments::getArguments(...),
        ]));
    }
}
