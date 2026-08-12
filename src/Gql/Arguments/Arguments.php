<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type ArgumentConfig from Argument */
abstract class Arguments
{
    /** @return array<string, ArgumentConfig> */
    public static function getArguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the elements’ IDs.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ UIDs.',
            ],
        ];
    }

    /** @return array<string, ArgumentConfig> */
    public static function getContentArguments(): array
    {
        return [];
    }
}
