<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use Craft;
use craft\helpers\StringHelper;

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
    public static function prefixTypeName(string $typeName): string
    {
        return self::_getPrefix() . $typeName;
    }

    /**
     * Get the type prefix.
     *
     * @return string|null
     */
    private static function _getPrefix()
    {
        if (self::$_prefix === null) {
            self::$_prefix = Craft::$app->getConfig()->getGeneral()->gqlTypePrefix;
        }

        return self::$_prefix;
    }

    /**
     * Get a registered entity.
     *
     * @param string $entityName
     * @return bool|mixed
     */
    public static function getEntity(string $entityName)
    {
        // Check if we need to apply the prefix.
        $prefix = self::_getPrefix();
        if ($prefix && !StringHelper::startsWith($entityName, $prefix)) {
            $entityName = self::prefixTypeName($entityName);
        }

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
        $entityName = self::prefixTypeName($entityName);
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
