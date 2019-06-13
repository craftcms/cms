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
     * @param string $entityName
     * @return bool|mixed
     */
    public static function getEntity(string $entityName)
    {
        return self::$_entities[$entityName] ?? false;
    }

    /**
     * Create an entity registry entry.
     *
     * @param string $entityName
     * @param mixed $entity
     * @return mixed
     */
    public static function createEntity(string $entityName, $entity)
    {
        self::$_entities[$entityName] = $entity;

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
