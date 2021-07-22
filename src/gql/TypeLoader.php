<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use craft\errors\GqlException;
use GraphQL\Type\Definition\Type;

/**
 * Class TypeLoader
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TypeLoader
{
    /**
     * @var callable[]
     */
    private static array $_typeLoaders = [];

    /**
     * @param string $type
     * @return Type
     * @throws GqlException
     */
    public static function loadType(string $type): Type
    {
        if (!empty(self::$_typeLoaders[$type])) {
            $loader = self::$_typeLoaders[$type];

            return $loader();
        }

        throw new GqlException('Tried to load an unregistered type "' . $type . '". This can indicate both a typo in the query or an issue with the schema used.');
    }

    /**
     * Register a type with a callable loader function.
     *
     * @param string $type
     * @param callable $loader
     */
    public static function registerType(string $type, callable $loader): void
    {
        self::$_typeLoaders[$type] = $loader;
    }

    /**
     * Flush all registered type loaders.
     */
    public static function flush(): void
    {
        self::$_typeLoaders = [];
    }
}
