<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input\Criteria;

use CraftCms\Cms\Gql\Arguments\Elements\User as UserArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class User extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'UserCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => UserArguments::getArguments(...),
        ]));
    }
}
