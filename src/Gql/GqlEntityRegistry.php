<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Cms;

class GqlEntityRegistry
{
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

    public static function getEntity(string $entityName): mixed
    {
        $entityName = self::prefixTypeName($entityName);

        return self::$_entities[$entityName] ?? false;
    }

    public static function createEntity(string $entityName, mixed $entity): mixed
    {
        $entityName = self::prefixTypeName($entityName);
        $entity->name = self::prefixTypeName($entity->name);

        self::$_entities[$entityName] = $entity;
        TypeLoader::registerType($entityName, fn () => $entity);

        return $entity;
    }

    public static function getOrCreate(string $name, callable $factory): mixed
    {
        $name = self::prefixTypeName($name);

        return self::$_entities[$name] ??= self::createEntity($name, $factory());
    }

    public static function flush(): void
    {
        self::$_entities = [];
    }
}
