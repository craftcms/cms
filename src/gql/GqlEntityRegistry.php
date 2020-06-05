<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft;

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
     * @var string
     */
    private static $_prefix = null;

    /**
     * Prefix GQL type name with the configured prefix.
     *
     * @param string $typeName
     * @return string
     */
    protected static function prefixTypeName(string $typeName): string
    {
        if (self::$_prefix === null) {
            self::$_prefix = Craft::$app->getConfig()->getGeneral()->gqlTypePrefix;
        }

        return self::$_prefix . $typeName;
    }

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
        $entity->name = self::prefixTypeName($entity->name);

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
