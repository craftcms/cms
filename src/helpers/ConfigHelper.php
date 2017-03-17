<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

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
     * @param int|string|\DateInterval $value The time duration value. Can either be an integer (number of seconds),
     *                                        a string with a valid [PHP time format](http://www.php.net/manual/en/datetime.formats.time.php),
     *                                        or a DateInterval object.
     *
     * @return int The time duration in seconds
     * @throws InvalidConfigException if $value is not one of the allowed types
     */
    public static function timeInSeconds($value): int
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

        return DateTimeHelper::dateIntervalToSeconds($value);
    }
}
