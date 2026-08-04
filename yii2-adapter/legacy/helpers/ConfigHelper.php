<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Support\Config;
use CraftCms\Cms\Support\PHP;

/**
 * Config helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see Config} instead.
 */
class ConfigHelper
{
    /**
     * Normalizes a time duration value into the number of seconds it represents.
     *
     * Accepted formats:
     * - integer (the duration in seconds)
     * - string (a [duration interval](https://en.wikipedia.org/wiki/ISO_8601#Durations))
     * - DateInterval object
     * - an empty value (represents 0 seconds)
     *
     * @param mixed $value
     * @return int The time duration in seconds
     * @throws \Exception if the duration can't be determined
     */
    public static function durationInSeconds(mixed $value): int
    {
        return Config::durationInSeconds($value);
    }

    /**
     * Normalizes a file size value into the number of bytes it represents.
     *
     * Accepted formats;
     * - integer (the size in bytes)
     * - string (a [shorthand byte value](https://php.net/manual/en/faq.using.php#faq.using.shorthandbytes) ending in `K` (Kilobytes), `M` (Megabytes), or `G` (Gigabytes))
     *
     * @param int|string $value The size
     * @return int|float The size in bytes
     * @deprecated 6.0.0 use {@see PHP::sizeToBytes()} instead.
     */
    public static function sizeInBytes(int|string $value): float|int
    {
        return PHP::sizeToBytes($value);
    }

    /**
     * Returns a localized config setting value.
     *
     * @param mixed $value The config setting value. This can be specified in one of the following forms:
     * - A scalar value or null: represents the desired value directly, and will be returned verbatim.
     * - An associative array: represents the desired values across all sites, indexed by site handles.
     *   If a matching site handle isn’t listed, the first value will be returned.
     * - A PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *   The callable will be passed the site handle if known, and should return the desired config value.
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return mixed
     */
    public static function localizedValue(mixed $value, ?string $siteHandle = null): mixed
    {
        return Config::localizedValue($value, $siteHandle);
    }
}
