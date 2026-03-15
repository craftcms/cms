<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use GraphQL\Type\Definition\Type;

class Ping extends Query
{
    public static function getQueries(bool $checkToken = true): array
    {
        return [
            'ping' => [
                'type' => Type::string(),
                'resolve' => fn () => 'pong',
            ],
        ];
    }
}
