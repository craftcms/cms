<?php
namespace craft\gql\common;

use craft\gql\types\DateTimeType;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseType
 */
abstract class SchemaObject
{
    /**
     * @var Type[]
     */
    protected static $_typeInstances = [];

    /**
     * @param string $className
     * @return bool|Type
     */
    protected static function hasType(string $className)
    {
        return self::$_typeInstances[$className] ?? false;
    }

    /**
     * @param string $className
     * @param Type $type
     * @return Type
     */
    protected static function createType(string $className, Type $type): Type
    {
        self::$_typeInstances[$className] = $type;
        return $type;
    }

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
}
