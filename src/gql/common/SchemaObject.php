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
    public static function getCommonFields(): array
    {
        return [
            'id' => Type::id(),
            'uid' => Type::string(),
            'dateCreated' => DateTimeType::instance(),
            'dateUpdated' =>  DateTimeType::instance(),
        ];
    }

    /**
     * Returns an instance of this schema object's type.
     *
     * @return Type
     */
    abstract public static function getType(): Type;

    /**
     * Returns the fields configured for this type.
     *
     * @return array
     */
    abstract public static function getFields(): array;
}
