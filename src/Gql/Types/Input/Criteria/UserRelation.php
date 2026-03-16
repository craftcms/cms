<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input\Criteria;

use CraftCms\Cms\Gql\Arguments\Elements\User as UserArguments;
use CraftCms\Cms\Gql\Arguments\RelationCriteria;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class UserRelation extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'UserRelationCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => fn () => UserArguments::getArguments() + RelationCriteria::getArguments(),
        ]));
    }
}
