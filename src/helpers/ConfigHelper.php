<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use yii\base\InvalidConfigException;

/**
 * Config helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ConfigHelper
{
    /**
     * Normalizes a time duration value into the number of seconds it represents.
     *
     * Accepted formats:
     *
     * - integer (the duration in seconds)
     * - string (a [duration interval](https://en.wikipedia.org/wiki/ISO_8601#Durations))
     * - DateInterval object
     * - an empty value (represents 0 seconds)
     *
     * @param mixed $value
     *
     * @return int The time duration in seconds
     * @throws InvalidConfigException if the duration can't be determined
     */
    public static function durationInSeconds($value): int
    {
        if (!$value) {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = new \DateInterval($value);
        }

        if (!$value instanceof \DateInterval) {
            throw new InvalidConfigException("Unable to convert {$value} to seconds.");
        }

        return DateTimeHelper::intervalToSeconds($value);
    }

    /**
     * Returns a localized config setting value.
     *
     * @param mixed       $value      The config setting value. If it's an array, the item
     *                                with a key that matches the site handle will be returned,
     *                                or the first value if that doesn't exist.
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     *
     * @return mixed
     */
    public static function localizedValue($value, string $siteHandle = null)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        if ($siteHandle === null) {
            $siteHandle = Craft::$app->getSites()->currentSite->handle;
        }

        if (array_key_exists($siteHandle, $value)) {
            return $value[$siteHandle];
        }

        // Just return the first value
        return reset($value);
    }
}
