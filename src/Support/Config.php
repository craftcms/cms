<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Carbon\CarbonInterval;
use CraftCms\Cms\Support\Facades\Sites;
use DateInterval;

final class Config
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
     * @return int The time duration in seconds
     *
     * @throws \Exception if the duration can't be determined
     */
    public static function durationInSeconds(int|string|null|DateInterval $value): int
    {
        if (! $value) {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        return (int) new CarbonInterval($value)->totalSeconds;
    }

    /**
     * Returns a localized config setting value.
     *
     * @param  mixed  $value  The config setting value. This can be specified in one of the following forms:
     *                        - A scalar value or null: represents the desired value directly, and will be returned verbatim.
     *                        - An associative array: represents the desired values across all sites, indexed by site handles.
     *                        If a matching site handle isn’t listed, the first value will be returned.
     *                        - A PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *                        The callable will be passed the site handle if known, and should return the desired config value.
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
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
            $siteHandle = Sites::getCurrentSite()->handle;
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
