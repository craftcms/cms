<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use GraphQL\Type\Definition\Type;

class NestedEntry extends Entry
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::id(),
                'description' => 'The entry’s owner ID.',
            ],
            'sortOrder' => [
                'name' => 'sortOrder',
                'type' => Type::int(),
                'description' => 'The entry’s sort order.',
            ],
        ]);
    }
}
