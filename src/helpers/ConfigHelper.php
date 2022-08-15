<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use DateInterval;
use yii\base\InvalidConfigException;

/**
 * Config helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
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
     * @throws InvalidConfigException if the duration can't be determined
     */
    public static function durationInSeconds(mixed $value): int
    {
        if (!$value) {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = new DateInterval($value);
        }

        if (!$value instanceof DateInterval) {
            throw new InvalidConfigException("Unable to convert to seconds.");
        }

        return DateTimeHelper::intervalToSeconds($value);
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
     */
    public static function sizeInBytes(int|string $value): float|int
    {
        // See if we can recognize that.
        if (is_numeric($value) || !preg_match('/(\d+)(K|M|G)/i', $value, $matches)) {
            return (int)$value;
        }

        $value = (int)$matches[1];

        // Multiply!
        switch (strtolower($matches[2])) {
            case 'g':
                $value *= 1024;
            // no break
            case 'm':
                $value *= 1024;
            // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
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
        if (is_scalar($value)) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        if ($siteHandle === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $siteHandle = Craft::$app->getSites()->getCurrentSite()->handle;
        }

        if (is_callable($value, true)) {
            return $value($siteHandle);
        }

        if (array_key_exists($siteHandle, $value)) {
            return $value[$siteHandle];
        }

        // Just return the first value
        return reset($value);
    }
}
