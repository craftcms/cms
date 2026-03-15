<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Gql\Exceptions\GqlException;
use GraphQL\Type\Definition\Type;

class TypeLoader
{
    /**
     * @var callable[]
     */
    private static array $_typeLoaders = [];

    /**
     * @throws GqlException
     */
    public static function loadType(string $type): Type
    {
        if (! empty(self::$_typeLoaders[$type])) {
            $loader = self::$_typeLoaders[$type];

            return $loader();
        }

        throw new GqlException('Tried to load an unregistered type "'.$type.'". This can indicate both a typo in the query or an issue with the schema used.');
    }

    public static function registerType(string $type, callable $loader): void
    {
        self::$_typeLoaders[$type] = $loader;
    }

    public static function flush(): void
    {
        self::$_typeLoaders = [];
    }
}
