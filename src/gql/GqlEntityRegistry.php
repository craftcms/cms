<?php
namespace craft\gql;

/**
 * Class GqlEntityRegistry
 */
class GqlEntityRegistry
{
    /**
     * @var array
     */
    private static $_entities = [];

    /**
     * @param string $className
     * @return bool|mixed
     */
    public static function getEntity(string $className)
    {
        return self::$_entities[$className] ?? false;
    }

    /**
     * @param string $className
     * @param mixed $entity
     * @return mixed
     */
    public static function createEntity(string $className, $entity)
    {
        self::$_entities[$className] = $entity;

        return $entity;
    }
}
