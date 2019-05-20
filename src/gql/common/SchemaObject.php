<?php
namespace craft\gql\common;

use craft\gql\TypeRegistry;
use craft\gql\types\DateTimeType;
use GraphQL\Type\Definition\ObjectType;
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
            'dateCreated' => DateTimeType::getType(),
            'dateUpdated' =>  DateTimeType::getType(),
        ];
    }

    /**
     * Returns an instance of this schema object's type.
     *
     * @param array $fields optional fields to use
     * @return Type
     */
    public static function getType($fields = null): Type
    {
        return TypeRegistry::getType(static::class) ?: TypeRegistry::createType(static::class, new ObjectType([
            'name' => static::getName(),
            'fields' => $fields ?: (static::class . '::getFields'),
        ]));
    }

    /**
     * Returns the fields configured for this type.
     *
     * @return array
     */
    abstract public static function getFields(): array;

    /**
     * Returns the schema object name
     *
     * @return string
     */
    abstract public static function getName(): string;
}
