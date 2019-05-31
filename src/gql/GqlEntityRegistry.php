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
     * Get a registered entity.
     *
     * @param string $className
     * @return bool|mixed
     */
    public static function getEntity(string $className)
    {
        return self::$_entities[$className] ?? false;
    }

    /**
     * Create an entity registry entry.
     *
     * @param string $className
     * @param mixed $entity
     * @return mixed
     */
    public static function createEntity(string $className, $entity)
    {
        self::$_entities[$className] = $entity;

        return $entity;
    }

    /**
     * Flush all registered entities.
     */
    public static function flush()
    {
        self::$_entities = [];
    }
}
