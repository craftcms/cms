<?php
namespace craft\gql;

use GraphQL\Type\Definition\Type;

/**
 * Class TypeRegistry
 */
class TypeRegistry
{
    /**
     * @var Type[]
     */
    private static $_typeInstances = [];

    /**
     * @param string $className
     * @return bool|Type
     */
    public static function getType(string $className)
    {
        return self::$_typeInstances[$className] ?? false;
    }

    /**
     * @param string $className
     * @param Type $type
     * @return Type
     */
    public static function createType(string $className, Type $type): Type
    {
        self::$_typeInstances[$className] = $type;

        return $type;
    }
}
