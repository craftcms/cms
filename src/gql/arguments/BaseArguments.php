<?php
namespace craft\gql\arguments;

use GraphQL\Type\Definition\Type;

/**
 * Class BaseArguments
 */
abstract class BaseArguments
{
    /**
     * Returns the argument fields to use in GQL type definitions.
     *
     * @return array $fields
     */
    public static function getArguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on the {elements}’ IDs.'
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the {elements}’ UIDs.'
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the {elements}’ creation dates.'
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the {elements}’ last-updated dates.'
            ],
            'offset' => [
                'name' => 'offset',
                'type' => Type::int(),
                'description' => 'Sets the offset for paginated results.'
            ],
            'limit' => [
                'name' => 'limit',
                'type' => Type::int(),
                'description' => 'Sets the limit for paginated results.'
            ],
            'orderBy' => [
                'name' => 'orderBy',
                'type' => Type::string(),
                'description' => 'Sets the field the returned elements should be ordered by'
            ],
        ];
    }
}
