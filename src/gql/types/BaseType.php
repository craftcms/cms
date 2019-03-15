<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class BaseType
 */
abstract class BaseType
{
    /**
     * @var ObjectType[]
     */
    protected static $_typeInstances = [];

    /**
     * @param string $className
     * @return bool|ObjectType
     */
    protected static function hasType(string $className)
    {
        return self::$_typeInstances[$className] ?? false;
    }

    /**
     * @param string $className
     * @param ObjectType $type
     * @return ObjectType
     */
    protected static function createType(string $className, ObjectType $type): ObjectType
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

    abstract public static function getType(): ObjectType;
}
