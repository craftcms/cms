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
            'id' => Type::int(),
            'uid' => Type::string(),
            'dateCreated' => DateTimeType::getType(),
            'dateUpdated' => DateTimeType::getType(),
            'offset' => Type::int(),
            'limit' => Type::int(),
            'orderBy' => Type::string(),
        ];
    }
}
