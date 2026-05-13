<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Support\DateTimeHelper as SupportDateTimeHelper;
use DateInterval;
use DateTime;
use DateTimeZone;
use yii\base\ErrorException;

/**
 * Class DateTimeHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see SupportDateTimeHelper} instead.
 */
class DateTimeHelper extends SupportDateTimeHelper
{
    /**
     * Returns a given timezone's offset from UTC (e.g. '+10:00' or '-06:00').
     *
     * @param string $timeZone
     * @return string
     * @deprecated in 4.3.7
     */
    public static function timeZoneOffset(string $timeZone): string
    {
        $offset = (new DateTimeZone($timeZone))
            ->getOffset(new DateTime('now', new DateTimeZone('UTC')));

        return sprintf(
            '%s%02d:%02d',
            $offset < 0 ? '-' : '+',
            abs($offset) / 3600,
            abs($offset) / 60 % 60
        );
    }

    /**
     * @param int $seconds The number of seconds
     * @param bool $showSeconds Whether to output seconds or not
     * @return string
     * @deprecated in 4.2.0. [[humanDuration()]] should be used instead.
     */
    public static function secondsToHumanTimeDuration(int $seconds, bool $showSeconds = true): string
    {
        return static::humanDuration($seconds, $showSeconds);
    }

    /**
     * Creates a DateInterval object based on a given number of seconds.
     *
     * @param int $seconds
     * @return DateInterval
     * @deprecated in 4.2.1. [[toDateInterval()]] should be used instead.
     */
    public static function secondsToInterval(int $seconds): DateInterval
    {
        return new DateInterval("PT{$seconds}S");
    }

    /**
     * Returns true if interval string is a valid interval.
     *
     * @param string $intervalString
     * @return bool
     */
    public static function isValidIntervalString(string $intervalString): bool
    {
        try {
            $interval = DateInterval::createFromDateString($intervalString);
        } catch (ErrorException) {
            return false;
        }

        return $interval->s != 0 || $interval->i != 0 || $interval->h != 0 || $interval->d != 0 || $interval->m != 0 || $interval->y != 0;
    }

    /**
     * Returns the interval in a human-friendly string.
     *
     * @param DateInterval $dateInterval
     * @param bool $showSeconds
     * @return string
     * @deprecated in 4.2.0. [[humanDuration()]] should be used instead.
     */
    public static function humanDurationFromInterval(DateInterval $dateInterval, bool $showSeconds = true): string
    {
        return static::humanDuration($dateInterval, $showSeconds);
    }
}
