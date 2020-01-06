<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

/**
 * Class GqlEntityRegistry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
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

        TypeLoader::registerType($entityName, function() use ($entity) {
            return $entity;
        });

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
