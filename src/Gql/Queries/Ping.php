<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
class Ping extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
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
