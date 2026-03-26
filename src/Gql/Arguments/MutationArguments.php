<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use GraphQL\Type\Definition\Type;

abstract class MutationArguments
{
    public static function getArguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
                'description' => 'Set the element’s ID.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => 'Set the element’s UID.',
            ],
        ];
    }
}
