<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Cms;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * @phpstan-type GqlEntity ScalarType|ObjectType|InterfaceType|UnionType|EnumType|InputObjectType|Directive
 *
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
 *
 * @since 6.0.0
 */
class GqlEntityRegistry
{
    /** @var array<string, GqlEntity> */
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

    /** @return GqlEntity|false */
    public static function getEntity(string $entityName): Type|Directive|false
    {
        $entityName = self::prefixTypeName($entityName);

        return self::$_entities[$entityName] ?? false;
    }

    /**
     * @template T of GqlEntity
     *
     * @param  T  $entity
     * @return T
     */
    public static function createEntity(string $entityName, Type|Directive $entity): Type|Directive
    {
        $entityName = self::prefixTypeName($entityName);
        $entity->name = self::prefixTypeName($entity->name);

        self::$_entities[$entityName] = $entity;
        if ($entity instanceof Type) {
            TypeLoader::registerType($entityName, fn () => $entity);
        }

        return $entity;
    }

    /**
     * @template T of GqlEntity
     *
     * @param  callable(): (GqlEntity)  $factory
     * @param  class-string<T>|null  $expectedType
     * @return ($expectedType is null ? (GqlEntity) : T)
     */
    public static function getOrCreate(string $name, callable $factory, ?string $expectedType = null): Type|Directive
    {
        $name = self::prefixTypeName($name);

        $entity = self::$_entities[$name] ??= self::createEntity($name, $factory());
        if ($expectedType !== null && ! $entity instanceof $expectedType) {
            throw new \InvalidArgumentException("GraphQL entity '$name' must be an instance of '$expectedType'.");
        }

        return $entity;
    }

    public static function flush(): void
    {
        self::$_entities = [];
    }
}
