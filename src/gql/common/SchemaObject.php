<?php
namespace craft\gql\common;

use craft\gql\types\DateTimeType;
use GraphQL\Type\Definition\Type;

/**
 * Class SchemaObject
 */
abstract class SchemaObject
{
    /**
     * A list of common fields.
     *
     * @return array
     */
    protected static function getCommonFields(): array
    {
        return [
            'id' => Type::id(),
            'uid' => Type::string(),
            'dateCreated' => DateTimeType::instance(),
            'dateUpdated' =>  DateTimeType::instance(),
        ];
    }

    abstract public static function getType(): Type;

    abstract public static function getFields(): array;
}
