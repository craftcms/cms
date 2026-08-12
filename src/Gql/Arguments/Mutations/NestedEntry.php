<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type ArgumentConfig from Argument */
class NestedEntry extends Entry
{
    /** @return array<string, ArgumentConfig> */
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
