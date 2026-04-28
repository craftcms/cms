<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input\Criteria;

use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class Entry extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'EntryCriteriaInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => fn () => [
                ...EntryArguments::getArguments(),
                ...EntryArguments::getContentArguments(),
            ],
        ]));
    }
}
