<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
class Ping extends Mutation
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
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
