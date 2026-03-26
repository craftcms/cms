<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use GraphQL\Type\Definition\Type;

class Ping extends Mutation
{
    public static function getMutations(): array
    {
        return [
            'ping' => [
                'type' => Type::string(),
                'resolve' => fn () => 'A mutated pong',
            ],
        ];
    }
}
