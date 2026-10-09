<?php

/**
 * @link https://craftcms.com/
 *
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
 *
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see SupportDateTimeHelper} instead.
 */
class DateTimeHelper extends SupportDateTimeHelper
{
    /**
     * @deprecated in 6.0.0. Use direct integer values or Carbon interval APIs instead.
     */
    public const int SECONDS_MINUTE = 60;

    /**
     * @deprecated in 6.0.0. Use direct integer values or Carbon interval APIs instead.
     */
    public const int SECONDS_HOUR = 3600;

    /**
     * @deprecated in 6.0.0. Use direct integer values or Carbon interval APIs instead.
     */
    public const int SECONDS_DAY = 86400;

    /**
     * @deprecated in 6.0.0. Use direct integer values or Carbon interval APIs instead.
     */
    public const int SECONDS_MONTH = 2629740;

    /**
     * @deprecated in 6.0.0. Use direct integer values or Carbon interval APIs instead.
     */
    public const int SECONDS_YEAR = 31556874;

    /**
     * Returns a DateTime object set to the current time.
     *
     * @deprecated in 6.0.0. Use Laravel's `now($timeZone)` helper instead.
     */
    public static function now(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return now($timeZone);
    }

    /**
     * Returns a DateTime object set to the current time in the UTC time zone.
     *
     * @deprecated in 6.0.0. Use Laravel's `now('UTC')` helper instead.
     */
    public static function currentUTCDateTime(): DateTime
    {
        return now('UTC');
    }

    /**
     * Returns the current Unix timestamp.
     *
     * @deprecated in 6.0.0. Use Laravel's `now()->getTimestamp()` instead.
     */
    public static function currentTimeStamp(): int
    {
        return now()->getTimestamp();
    }

    /**
     * Returns a DateTime object set to midnight of the current day.
     *
     * @deprecated in 6.0.0. Use Laravel's `today($timeZone)` helper instead.
     */
    public static function today(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::now($timeZone)->setTime(0, 0);
    }

    /**
     * Returns a DateTime object set to midnight of the following day.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->addDay()` instead.
     */
    public static function tomorrow(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::today($timeZone)->modify('+1 day');
    }

    /**
     * Returns a DateTime object set to midnight of the previous day.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->subDay()` instead.
     */
    public static function yesterday(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::today($timeZone)->modify('-1 day');
    }

    /**
     * Returns a DateTime object set to midnight of the first day of this week, according to the user's preferences.
     *
     * @deprecated in 6.0.0. Use `now($timeZone)->startOfWeek(DateTimeHelper::firstWeekDay())` instead.
     */
    public static function thisWeek(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        $today = static::today($timeZone);
        $dayOfWeek = (int) $today->format('N');

        if ($dayOfWeek === 7) {
            $dayOfWeek = 0;
        }

        $startDay = static::firstWeekDay();

        if ($dayOfWeek === $startDay) {
            return $today;
        }

        $diff = $dayOfWeek > $startDay
            ? $dayOfWeek - $startDay
            : ($dayOfWeek + 7) - $startDay;

        return $today->modify("-$diff days");
    }

    /**
     * Returns a DateTime object set to midnight of the first day of next week, according to the user's preferences.
     *
     * @deprecated in 6.0.0. Use `now($timeZone)->startOfWeek(DateTimeHelper::firstWeekDay())->addWeek()` instead.
     */
    public static function nextWeek(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::thisWeek($timeZone)->modify('+1 week');
    }

    /**
     * Returns a DateTime object set to midnight of the first day of last week, according to the user's preferences.
     *
     * @deprecated in 6.0.0. Use `now($timeZone)->startOfWeek(DateTimeHelper::firstWeekDay())->subWeek()` instead.
     */
    public static function lastWeek(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::thisWeek($timeZone)->modify('-1 week');
    }

    /**
     * Returns a DateTime object set to midnight of the first day of this month.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->startOfMonth()` instead.
     */
    public static function thisMonth(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        $today = static::today($timeZone);

        return $today->setDate((int) $today->format('Y'), (int) $today->format('n'), 1);
    }

    /**
     * Returns a DateTime object set to midnight of the first day of next month.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->startOfMonth()->addMonth()` instead.
     */
    public static function nextMonth(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::thisMonth($timeZone)->modify('+1 month');
    }

    /**
     * Returns a DateTime object set to midnight of the first day of last month.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->startOfMonth()->subMonth()` instead.
     */
    public static function lastMonth(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::thisMonth($timeZone)->modify('-1 month');
    }

    /**
     * Returns a DateTime object set to midnight of the first day of this year.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->startOfYear()` instead.
     */
    public static function thisYear(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        $today = static::today($timeZone);

        return $today->setDate((int) $today->format('Y'), 1, 1);
    }

    /**
     * Returns a DateTime object set to midnight of the first day of next year.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->startOfYear()->addYear()` instead.
     */
    public static function nextYear(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::thisYear($timeZone)->modify('+1 year');
    }

    /**
     * Returns a DateTime object set to midnight of the first day of last year.
     *
     * @deprecated in 6.0.0. Use `today($timeZone)->startOfYear()->subYear()` instead.
     */
    public static function lastYear(DateTimeZone|string|int|null $timeZone = null): DateTime
    {
        return static::thisYear($timeZone)->modify('-1 year');
    }

    /**
     * Returns true if given date is today.
     *
     * @deprecated in 6.0.0. Use Carbon's `isToday()` method instead.
     */
    public static function isToday(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('Y-m-d') == $now->format('Y-m-d');
    }

    /**
     * Returns true if given date was yesterday.
     *
     * @deprecated in 6.0.0. Use Carbon's `isYesterday()` method instead.
     */
    public static function isYesterday(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $yesterday = static::now()->modify('-1 day');

        return $date->format('Y-m-d') == $yesterday->format('Y-m-d');
    }

    /**
     * Returns true if given date is in this year.
     *
     * @deprecated in 6.0.0. Use Carbon's `isCurrentYear()` method instead.
     */
    public static function isThisYear(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('Y') == $now->format('Y');
    }

    /**
     * Returns true if given date is in this week.
     *
     * @deprecated in 6.0.0. Use Carbon's `isCurrentWeek()` method instead.
     */
    public static function isThisWeek(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('W Y') == $now->format('W Y');
    }

    /**
     * Returns true if given date is in this month.
     *
     * @deprecated in 6.0.0. Use Carbon's `isCurrentMonth()` method instead.
     */
    public static function isThisMonth(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('m Y') == $now->format('m Y');
    }

    /**
     * Returns true if specified datetime was within the interval specified, else false.
     *
     * @deprecated in 6.0.0. Use Carbon comparison methods such as `isAfter()` and `sub()` instead.
     */
    public static function isWithinLast(mixed $date, mixed $timeInterval): bool
    {
        $date = static::toDateTime($date);

        if ($date === false) {
            throw new \InvalidArgumentException('Invalid date');
        }

        $timestamp = $date->getTimestamp();
        $now = static::now();

        if ($timestamp > $now->getTimestamp()) {
            return false;
        }

        if (is_numeric($timeInterval)) {
            $timeInterval .= ' days';
        }

        try {
            $earliestTimestamp = $now->modify("-$timeInterval")->getTimestamp();
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException("Invalid time interval: $timeInterval", 0, $throwable);
        }

        return $timestamp >= $earliestTimestamp;
    }

    /**
     * Returns true if the specified date was in the past, otherwise false.
     *
     * @deprecated in 6.0.0. Use Carbon's `isPast()` method instead.
     */
    public static function isInThePast(mixed $date): bool
    {
        return static::toDateTime($date)->getTimestamp() < static::currentTimeStamp();
    }

    /**
     * Returns the number of seconds that a given DateInterval object spans.
     *
     * @deprecated in 6.0.0. Use Carbon interval/date difference APIs instead.
     */
    public static function intervalToSeconds(DateInterval $dateInterval): int
    {
        $reference = static::now();
        $endTime = (clone $reference)->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * Converts a relative time (number and unit) to seconds.
     *
     * @deprecated in 6.0.0. Use Carbon's `diffInSeconds()` method instead.
     */
    public static function relativeTimeToSeconds(int $number, string $unit): int
    {
        $now = now();

        return (int) $now->diffInSeconds((clone $now)->add($number, $unit));
    }

    /**
     * Returns a relative time statement based on the given number and unit.
     *
     * @deprecated in 6.0.0. Use Carbon date modification methods such as `now()->addDays()` instead.
     */
    public static function relativeTimeStatement(int $number, string $unit): string
    {
        if ($unit === 'week') {
            if ($number === 1) {
                $number = 7;
                $unit = 'days';
            } else {
                $unit = 'weeks';
            }
        }

        return "+$number $unit";
    }

    /**
     * Returns a given timezone's offset from UTC (e.g. '+10:00' or '-06:00').
     *
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
     * @param  int  $seconds  The number of seconds
     * @param  bool  $showSeconds  Whether to output seconds or not
     *
     * @deprecated in 4.2.0. [[humanDuration()]] should be used instead.
     */
    public static function secondsToHumanTimeDuration(int $seconds, bool $showSeconds = true): string
    {
        return static::humanDuration($seconds, $showSeconds);
    }

    /**
     * Creates a DateInterval object based on a given number of seconds.
     *
     * @deprecated in 4.2.1. [[toDateInterval()]] should be used instead.
     */
    public static function secondsToInterval(int $seconds): DateInterval
    {
        return new DateInterval("PT{$seconds}S");
    }

    /**
     * Returns true if interval string is a valid interval.
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
     * @deprecated in 4.2.0. [[humanDuration()]] should be used instead.
     */
    public static function humanDurationFromInterval(DateInterval $dateInterval, bool $showSeconds = true): string
    {
        return static::humanDuration($dateInterval, $showSeconds);
    }
}
