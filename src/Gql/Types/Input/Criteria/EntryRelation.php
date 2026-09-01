<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input\Criteria;

use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\Arguments\RelationCriteria;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class EntryRelation extends InputObjectType
{
    public static function getType(): InputObjectType
    {
        $typeName = 'EntryRelationCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => fn () => [
                ...EntryArguments::getArguments(),
                ...EntryArguments::getContentArguments(),
                ...RelationCriteria::getArguments(),
            ],
        ]));
    }
}
