<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Cms;

/**
 * Stores generated GraphQL entities for reuse while building a schema.
 *
 * ```php
 * public static function create(): ObjectType
 * {
 *     return GqlEntityRegistry::getOrCreate('Product', fn () => new ObjectType([
 *         'name' => 'Product',
 *         'fields' => [],
 *     ]));
 * }
 * ```
 */
class GqlEntityRegistry
{
    /** @var array<string, object> */
    private static array $_entities = [];

    private static string $_prefix;

    public static function prefixTypeName(string $typeName): string
    {
        $prefix = self::getPrefix();

        if (! $prefix || str_starts_with($typeName, $prefix)) {
            return $typeName;
        }

        $rootTypes = ['Query', 'Mutation', 'Subscription'];

        if (Cms::config()->prefixGqlRootTypes || ! in_array($typeName, $rootTypes)) {
            return $prefix.$typeName;
        }

        return $typeName;
    }

    public static function getPrefix(): ?string
    {
        if (! isset(self::$_prefix)) {
            self::$_prefix = Cms::config()->gqlTypePrefix;
        }

        return self::$_prefix;
    }

    public static function setPrefix(string $prefix): void
    {
        self::$_prefix = $prefix;
    }

    public static function getEntity(string $entityName): object|false
    {
        $entityName = self::prefixTypeName($entityName);

        return self::$_entities[$entityName] ?? false;
    }

    /**
     * @template T of object
     *
     * @param  T  $entity
     * @return T
     */
    public static function createEntity(string $entityName, object $entity): object
    {
        $entityName = self::prefixTypeName($entityName);
        $entity->name = self::prefixTypeName($entity->name);

        self::$_entities[$entityName] = $entity;
        TypeLoader::registerType($entityName, fn () => $entity);

        return $entity;
    }

    /**
     * @template T of object
     *
     * @param  callable(): T  $factory
     * @return T
     */
    public static function getOrCreate(string $name, callable $factory): object
    {
        $name = self::prefixTypeName($name);

        return self::$_entities[$name] ??= self::createEntity($name, $factory());
    }

    public static function flush(): void
    {
        self::$_entities = [];
    }
}
