<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Type\Definition\Type;

abstract class Arguments
{
    /**
     * @return array $fields
     */
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

    public static function getContentArguments(): array
    {
        return [];
    }
}
