<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\docs\resolvers;

use ReflectionClass;

/**
 * Resolver for PHP built-ins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class Php extends BaseResolver
{
    static $scalarTypes = [
        'array',
        'boolean',
        'int',
        'string',
    ];

    static function match(string $className): bool
    {
        $isScalar = in_array($className, static::$scalarTypes);

        if ($isScalar) {
            return true;
        }

        $reflection = new ReflectionClass($className);

        return !$reflection->isUserDefined();
    }

    static function getBaseUrl(): string
    {
        return 'https://www.php.net/manual/en/';
    }

    static function getPath(string $className, string $member = null, string $memberType = null): string
    {
        $path = strtolower($className);

        if ($member !== null) {
            $path = match ($memberType) {
                'method' => $path . '.' . strtolower($member) . '.php',
                'property' => $path . '.php#' . strtolower($className) . '.props.' . strtolower(str_replace($member, '_', '-')),
                'constant' => $path . '.php#' . strtolower($className) . '.constant.' . strtolower(str_replace($member, '_', '-')),
            };
        } else {
            $path = "class.$path.php";
        }

        return $path;
    }
}
