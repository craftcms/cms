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
    private static array $_entities = [];

    /**
     * @var string
     */
    private static string $_prefix;

    /**
     * Prefix GQL type name with the configured prefix.
     *
     * @param string $typeName
     * @return string
     */
    public static function prefixTypeName(string $typeName): string
    {
        $rootTypes = ['Query', 'Mutation', 'Subscription'];

        if (Craft::$app->getConfig()->getGeneral()->prefixGqlRootTypes || !in_array($typeName, $rootTypes)) {
            return self::getPrefix() . $typeName;
        }

        return $typeName;
    }

    /**
     * Returns the type prefix.
     *
     * @return string|null
     * @since 3.6.0
     */
    public static function getPrefix(): ?string
    {
        if (!isset(self::$_prefix)) {
            self::$_prefix = Craft::$app->getConfig()->getGeneral()->gqlTypePrefix;
        }

        return self::$_prefix;
    }

    /**
     * Sets the type prefix.
     *
     * @param string $prefix
     * @since 3.6.0
     */
    public static function setPrefix(string $prefix): void
    {
        self::$_prefix = $prefix;
    }

    /**
     * Get a registered entity.
     *
     * @param string $entityName
     * @return mixed
     */
    public static function getEntity(string $entityName): mixed
    {
        // Check if we need to apply the prefix.
        $prefix = self::getPrefix();
        if ($prefix && !str_starts_with($entityName, $prefix)) {
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
    public static function createEntity(string $entityName, mixed $entity): mixed
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
    public static function flush(): void
    {
        self::$_entities = [];
    }
}
