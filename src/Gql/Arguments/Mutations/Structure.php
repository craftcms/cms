<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use GraphQL\Type\Definition\Type;

class Structure
{
    public static function getArguments(): array
    {
        return [
            'prependTo' => [
                'name' => 'prependTo',
                'type' => Type::id(),
                'description' => 'The ID of the element to prepend to.',
            ],
            'appendTo' => [
                'name' => 'appendTo',
                'type' => Type::id(),
                'description' => 'The ID of the element to append to.',
            ],
            'prependToRoot' => [
                'name' => 'prependToRoot',
                'type' => Type::boolean(),
                'description' => 'Whether to prepend this element to the root.',
            ],
            'appendToRoot' => [
                'name' => 'appendToRoot',
                'type' => Type::boolean(),
                'description' => 'Whether to append this element to the root.',
            ],
            'insertBefore' => [
                'name' => 'insertBefore',
                'type' => Type::id(),
                'description' => 'The ID of the element this element should be inserted before.',
            ],
            'insertAfter' => [
                'name' => 'insertAfter',
                'type' => Type::id(),
                'description' => 'The ID of the element this element should be inserted after.',
            ],
        ];
    }
}
