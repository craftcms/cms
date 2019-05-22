<?php
namespace craft\gql\arguments;

use craft\gql\types\DateTimeType;
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
            'id' => Type::listOf(Type::int()),
            'uid' => Type::listOf(Type::string()),
            'dateCreated' => Type::string(),
            'dateUpdated' => Type::string(),
            'offset' => Type::int(),
            'limit' => Type::int(),
            'orderBy' => Type::string(),
        ];
    }
}
