<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\docs\resolvers;

/**
 * Base resolver class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
abstract class BaseResolver
{
    /**
     * Test the fully-qualified class name and return whether this resolver can/should handle it.
     */
    abstract public static function match(string $className): bool;

    /**
     * Return the base URL for the corresponding documentation or source.
     */
    abstract public static function getBaseUrl(): string;

    /**
     * Builds a complete URL from the class name (and optional class member + type).
     * 
     * @param string $className Fully-qualified class name.
     * @param string $member Name of a class method, property, or constant.
     * @param string $memberType How the resolver should treat the member name.
     */
    static function getUrl(string $className, string $member = null, string $memberType = null): string
    {
        return static::getBaseUrl() . static::getPath($className, $member, $memberType);
    }

    /**
     * Transform a class identifier into a path.
     */
    abstract public static function getPath(string $className, string $member = null, string $memberType = null): string;
}
