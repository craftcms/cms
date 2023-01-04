<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\i18n\Locale;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Throwable;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;

/**
 * Class DateTimeHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DateTimeHelper
{
    /**
     * @var int Number of seconds in a minute.
     */
    public const SECONDS_MINUTE = 60;

    /**
     * @var int Number of seconds in an hour.
     */
    public const SECONDS_HOUR = 3600;

    /**
     * @var int Number of seconds in a day.
     */
    public const SECONDS_DAY = 86400;

    /**
     * @var int The number of seconds in a month.
     *
     * Based on a 30.4368 day month, with the product rounded.
     */
    public const SECONDS_MONTH = 2629740;

    /**
     * @var int The number of seconds in a year.
     *
     * Based on a 365.2416 day year, with the product rounded.
     */
    public const SECONDS_YEAR = 31556874;

    /**
     * @var DateTime[]
     * @see pause()
     * @see resume()
     */
    private static array $_now = [];

    /**
     * Converts a value into a DateTime object.
     *
     * `$value` can be in the following formats:
     *
     *  - All W3C date and time formats (http://www.w3.org/TR/NOTE-datetime)
     *  - MySQL DATE and DATETIME formats (http://dev.mysql.com/doc/refman/5.1/en/datetime.html)
     *  - Relaxed versions of W3C and MySQL formats (single-digit months, days, and hours)
     *  - Unix timestamps
     * - `now`/`today`/`tomorrow`/`yesterday` (midnight of the specified relative date)
     *  - An array with at least one of these keys defined: `datetime`, `date`, or `time`. Supported keys include:
     *      - `date` – a date string in `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS.MU` formats or the current locale’s short date format
     *      - `time` – a time string in `HH:MM` or `HH:MM:SS` (24-hour) format or the current locale’s short time format
     *      - `datetime` – A timestamp in any of the non-array formats supported by this method
     *      - `timezone` – A [valid PHP timezone](https://php.net/manual/en/timezones.php). If set, this will override
     *        the assumed timezone per `$assumeSystemTimeZone`.
     *
     * @param string|int|array|DateTime|null $value The value that should be converted to a DateTime object.
     * @param bool $assumeSystemTimeZone Whether it should be assumed that the value was set in the system timezone if
     * the timezone was not specified. If this is `false`, UTC will be assumed.
     * @param bool $setToSystemTimeZone Whether to set the resulting DateTime object to the system timezone.
     * @return DateTime|false The DateTime object, or `false` if $object could not be converted to one
     * @throws Exception
     */
    public static function toDateTime(mixed $value, bool $assumeSystemTimeZone = false, bool $setToSystemTimeZone = true): DateTime|false
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        if (!$value) {
            return false;
        }

        $defaultTimeZone = ($assumeSystemTimeZone ? Craft::$app->getTimeZone() : 'UTC');

        if (is_array($value)) {
            if (empty($value['datetime']) && empty($value['date']) && empty($value['time'])) {
                return false;
            }

            // Did they specify a timezone?
            if (!empty($value['timezone']) && ($normalizedTimeZone = static::normalizeTimeZone($value['timezone'])) !== false) {
                $timeZone = $normalizedTimeZone;
            } else {
                $timeZone = $defaultTimeZone;
            }

            // Did they specify a full timestamp ?
            if (!empty($value['datetime'])) {
                $dt = self::_parseDateTime($value['datetime'], $timeZone);
                if ($dt === null) {
                    return false;
                }
            } else {
                // Did they specify a date?
                if (!empty($value['date'])) {
                    [$date, $format] = self::_parseDate($value['date']);
                } else {
                    // Default to the current date
                    $format = 'Y-m-d';
                    $date = static::now(new DateTimeZone($timeZone))->format($format);
                }

                // Did they specify a time?
                if (!empty($value['time'])) {
                    [$time, $timeFormat] = self::_parseTime($value['time']);
                    $format .= ' ' . $timeFormat;
                    $date .= ' ' . $time;
                }

                // Add the timezone
                $format .= ' e';
                $date .= ' ' . $timeZone;

                $dt = DateTime::createFromFormat("!$format", $date);
                if ($dt === false) {
                    return false;
                }
            }
        } else {
            $dt = self::_parseDateTime($value, $defaultTimeZone);
            if ($dt === null) {
                return false;
            }
        }

        if ($setToSystemTimeZone) {
            $dt->setTimezone(new DateTimeZone(Craft::$app->getTimeZone()));
        }

        return $dt;
    }

    /**
     * Normalizes a timezone string to a PHP timezone identifier.
     *
     * Supports the following formats:
     *  - Time zone abbreviation (EST, MDT)
     *  - Difference to Greenwich time (GMT) in hours, with/without a colon between the hours and minutes (+0200, -0200, +02:00, -02:00)
     *  - A PHP timezone identifier (UTC, GMT, Atlantic/Azores)
     *
     * @param string $timeZone The timezone to be normalized
     * @return string|false The PHP timezone identifier, or `false` if it could not be determined
     */
    public static function normalizeTimeZone(string $timeZone): string|false
    {
        // Is it already a PHP timezone identifier?
        if (in_array($timeZone, timezone_identifiers_list(), true)) {
            return $timeZone;
        }

        // Is this a timezone abbreviation?
        if (($timeZoneName = timezone_name_from_abbr($timeZone)) !== false) {
            return $timeZoneName;
        }

        // Is it the difference to GMT?
        if (preg_match('/[+\-]\d\d\:?\d\d/', $timeZone, $matches)) {
            $format = str_contains($timeZone, ':') ? 'e' : 'O';
            $dt = DateTime::createFromFormat($format, $timeZone, new DateTimeZone('UTC'));

            if ($dt !== false) {
                return $dt->format('e');
            }
        }

        // Dunno
        return false;
    }

    /**
     * Returns the timezone abbreviation for a given timezone name.
     *
     * @param string $timeZone
     * @return string
     */
    public static function timeZoneAbbreviation(string $timeZone): string
    {
        return (new DateTime())
            ->setTimezone(new DateTimeZone($timeZone))
            ->format('T');
    }

    /**
     * Returns a given timezone’s offset from UTC (e.g. '+10:00' or '-06:00').
     *
     * @param string $timeZone
     * @return string
     */
    public static function timeZoneOffset(string $timeZone): string
    {
        $offset = (new DateTimeZone($timeZone))
            ->getOffset(new DateTime('now', new DateTimeZone('UTC')));

        // Adapted from http://stackoverflow.com/a/13822928/1688568
        return sprintf('%s%02d:%02d',
            $offset < 0 ? '-' : '+',
            abs($offset) / 3600,
            abs($offset) / 60 % 60);
    }

    /**
     * Determines whether the given value is an ISO-8601-formatted date, as formatted by either
     * [DateTime::ATOM](https://php.net/manual/en/class.datetime.php#datetime.constants.atom) or
     * [DateTime::ISO8601](https://php.net/manual/en/class.datetime.php#datetime.constants.iso8601) (with or without
     * the colon between the hours and minutes of the timezone).
     *
     * @param mixed $value The timestamp to check
     * @return bool Whether the value is an ISO-8601 date string
     */
    public static function isIso8601(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d[\+\-]\d\d\:?\d\d$/', $value);
    }

    /**
     * Converts a date to an ISO-8601 string.
     *
     * @param mixed $date The date, in any format that [[toDateTime()]] supports.
     * @return string|false The date formatted as an ISO-8601 string, or `false` if $date was not a valid date
     */
    public static function toIso8601(mixed $date): string|false
    {
        $date = static::toDateTime($date);

        if ($date !== false) {
            return $date->format(DateTime::ATOM);
        }

        return false;
    }

    /**
     * Pauses time for any subsequent calls to other `DateTimeHelper` methods, until [[resume()]] is called.
     *
     * If this method is called multiple times, [[resume()]] will need to be called an equal number of times before
     * time is actually resumed.
     *
     * @param DateTime|null $now A `DateTime` object that should represent the current time for the duration of the pause
     * @since 4.1.0
     */
    public static function pause(?DateTime $now = null): void
    {
        array_unshift(self::$_now, $now ?? self::$_now[0] ?? new DateTime('now'));
    }

    /**
     * Resumes time, if it was paused via [[pause()]].
     *
     * @since 4.1.0
     */
    public static function resume(): void
    {
        array_shift(self::$_now);
    }

    /**
     * Returns a [[DateTime]] object set to the current time (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.1.0
     */
    public static function now(?DateTimeZone $timeZone = null): DateTime
    {
        // Is time paused?
        if (!empty(self::$_now)) {
            $date = clone self::$_now[0];
            $date->setTimezone($timeZone ?? new DateTimeZone(Craft::$app->getTimeZone()));
            return $date;
        }

        return new DateTime('now', $timeZone);
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the current day (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function today(?DateTimeZone $timeZone = null): DateTime
    {
        return static::now($timeZone)->setTime(0, 0);
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the following day (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function tomorrow(?DateTimeZone $timeZone = null): DateTime
    {
        return static::today($timeZone)->modify('+1 day');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the previous day (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function yesterday(?DateTimeZone $timeZone = null): DateTime
    {
        return static::today($timeZone)->modify('-1 day');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of this week, according to the user’s preferences (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function thisWeek(?DateTimeZone $timeZone = null): DateTime
    {
        $today = static::today($timeZone);
        $dayOfWeek = (int)$today->format('N');
        if ($dayOfWeek === 7) {
            $dayOfWeek = 0;
        }
        $startDay = static::firstWeekDay();

        // Is today the first day of the week?
        if ($dayOfWeek === $startDay) {
            return $today;
        }

        if ($dayOfWeek > $startDay) {
            $diff = $dayOfWeek - $startDay;
        } else {
            $diff = ($dayOfWeek + 7) - $startDay;
        }

        return $today->modify("-$diff days");
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of next week, according to the user’s preferences (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function nextWeek(?DateTimeZone $timeZone = null): DateTime
    {
        return static::thisWeek($timeZone)->modify('+1 week');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of last week, according to the user’s preferences (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function lastWeek(?DateTimeZone $timeZone = null): DateTime
    {
        return static::thisWeek($timeZone)->modify('-1 week');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of this month (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function thisMonth(?DateTimeZone $timeZone = null): DateTime
    {
        $today = static::today($timeZone);
        return $today->setDate((int)$today->format('Y'), (int)$today->format('n'), 1);
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of next month (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function nextMonth(?DateTimeZone $timeZone = null): DateTime
    {
        return static::thisMonth($timeZone)->modify('+1 month');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of last month (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function lastMonth(?DateTimeZone $timeZone = null): DateTime
    {
        return static::thisMonth($timeZone)->modify('-1 month');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of this year (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function thisYear(?DateTimeZone $timeZone = null): DateTime
    {
        $today = static::today($timeZone);
        return $today->setDate((int)$today->format('Y'), 1, 1);
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of next year (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function nextYear(?DateTimeZone $timeZone = null): DateTime
    {
        return static::thisMonth($timeZone)->modify('+1 year');
    }

    /**
     * Returns a [[DateTime]] object set to midnight of the first day of last year (factoring in whether time is [[pause()|paused]]).
     *
     * @param DateTimeZone|null $timeZone The time zone to return the `DateTime` object in. (Defaults to the system time zone.)
     * @return DateTime
     * @since 4.3.0
     */
    public static function lastYear(?DateTimeZone $timeZone = null): DateTime
    {
        return static::thisMonth($timeZone)->modify('-1 year');
    }

    /**
     * Returns a [[DateTime]] object set to the current time (factoring in whether time is [[pause()|paused]]), in the UTC time zone.
     *
     * @return DateTime
     */
    public static function currentUTCDateTime(): DateTime
    {
        return static::now(new DateTimeZone('UTC'));
    }

    /**
     * Returns the current Unix time stamp (factoring in whether time is [[pause()|paused]]).
     *
     * @return int
     */
    public static function currentTimeStamp(): int
    {
        $date = static::currentUTCDateTime();

        return $date->getTimestamp();
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
     * @param mixed $timestamp
     * @return bool
     */
    public static function isValidTimeStamp(mixed $timestamp): bool
    {
        if (!is_numeric($timestamp)) {
            return false;
        }

        $timestamp = (int)$timestamp;

        return $timestamp <= PHP_INT_MAX && $timestamp >= ~PHP_INT_MAX;
    }

    /**
     * Returns true if given date is today.
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date is today, false otherwise.
     */
    public static function isToday(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('Y-m-d') == $now->format('Y-m-d');
    }

    /**
     * Returns true if given date was yesterday
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date was yesterday, false otherwise.
     */
    public static function isYesterday(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $yesterday = static::now()->modify('-1 day');

        return $date->format('Y-m-d') == $yesterday->format('Y-m-d');
    }

    /**
     * Returns true if given date is in this year
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date is in this year, false otherwise.
     */
    public static function isThisYear(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('Y') == $now->format('Y');
    }

    /**
     * Returns true if given date is in this week
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date is in this week, false otherwise.
     */
    public static function isThisWeek(mixed $date): bool
    {
        $date = static::toDateTime($date);
        $now = static::now();

        return $date->format('W Y') == $now->format('W Y');
    }

    /**
     * Returns true if given date is in this month
     *
     * @param mixed $date The timestamp to check
     * @return bool True if date is in this month, false otherwise.
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
     * @param mixed $date The timestamp to check
     * @param mixed $timeInterval The numeric value with space then time type.
     * Example of valid types: '6 hours', '2 days', '1 minute'.
     * @return bool Whether the $dateString was within the specified $timeInterval.
     * @throws InvalidArgumentException
     */
    public static function isWithinLast(mixed $date, mixed $timeInterval): bool
    {
        $date = static::toDateTime($date);

        if ($date === false) {
            throw new InvalidArgumentException('Invalid date');
        }

        $timestamp = $date->getTimestamp();
        $now = static::now();

        // Bail early if it's in the future
        if ($timestamp > $now->getTimestamp()) {
            return false;
        }

        if (is_numeric($timeInterval)) {
            $timeInterval .= ' days';
        }

        try {
            $earliestTimestamp = $now->modify("-$timeInterval")->getTimestamp();
        } catch (Throwable $e) {
            throw new InvalidArgumentException("Invalid time interval: $timeInterval", 0, $e);
        }

        return $timestamp >= $earliestTimestamp;
    }

    /**
     * Returns true if the specified date was in the past, otherwise false.
     *
     * @param mixed $date The timestamp to check
     * @return bool true if the specified date was in the past, false otherwise.
     */
    public static function isInThePast(mixed $date): bool
    {
        $date = static::toDateTime($date);

        return $date->getTimestamp() < time();
    }

    /**
     * Converts a value into a DateInterval object.
     *
     * @param mixed $value The value, represented as either a [[\DateInterval]] object, an interval duration string, or a number of seconds.
     * @return DateInterval|false
     * @throws InvalidArgumentException
     * @since 4.2.1
     */
    public static function toDateInterval(mixed $value): DateInterval|false
    {
        if ($value instanceof DateInterval) {
            return $value;
        }

        if (!$value) {
            return false;
        }

        if (is_numeric($value)) {
            // Use DateTime::diff() so the years/months/days/hours/minutes values are all populated correctly
            $now = static::now(new DateTimeZone('UTC'));
            $then = (clone $now)->modify("+$value seconds");
            return $now->diff($then);
        }

        if (is_string($value)) {
            try {
                return new DateInterval($value);
            } catch (Exception $e) {
            }
        }

        throw new InvalidArgumentException('Unable to convert the value to a DateInterval.', 0, $e ?? null);
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
     * Returns the number of seconds that a given DateInterval object spans.
     *
     * @param DateInterval $dateInterval
     * @return int
     */
    public static function intervalToSeconds(DateInterval $dateInterval): int
    {
        $reference = new DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
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
     * Returns a human-friendly duration string for the given date interval or number of seconds.
     *
     * @param mixed $dateInterval The value, represented as either a [[\DateInterval]] object, an interval duration string, or a number of seconds.
     * @param bool|null $showSeconds Whether the duration string should include the number of seconds
     * @return string
     * @since 4.2.0
     */
    public static function humanDuration(mixed $dateInterval, ?bool $showSeconds = null): string
    {
        $dateInterval = static::toDateInterval($dateInterval) ?: new DateInterval('PT0S');
        $secondsOnly = !$dateInterval->y && !$dateInterval->m && !$dateInterval->d && !$dateInterval->h && !$dateInterval->i;

        if ($showSeconds === null) {
            $showSeconds = $secondsOnly;
        }

        $timeComponents = [];

        if ($dateInterval->y) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{year} other{years}}', ['num' => $dateInterval->y]);
        }

        if ($dateInterval->m) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{month} other{months}}', ['num' => $dateInterval->m]);
        }

        if ($dateInterval->d) {
            // Is it an exact number of weeks?
            if ($dateInterval->d % 7 === 0) {
                $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{week} other{weeks}}', ['num' => $dateInterval->d / 7]);
            } else {
                $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{day} other{days}}', ['num' => $dateInterval->d]);
            }
        }

        if ($dateInterval->h) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{hour} other{hours}}', ['num' => $dateInterval->h]);
        }

        $minutes = $dateInterval->i;

        if (!$showSeconds) {
            $addlMinutes = round($dateInterval->s / 60);
            if ($addlMinutes) {
                $minutes += $addlMinutes;
            } elseif ($secondsOnly) {
                return Craft::t('app', 'less than a minute');
            }
        }

        if ($minutes) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{minute} other{minutes}}', ['num' => $minutes]);
        }

        if ($showSeconds && ($dateInterval->s || empty($timeComponents))) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{second} other{seconds}}', ['num' => $dateInterval->s]);
        }

        $last = array_pop($timeComponents);
        if (!empty($timeComponents)) {
            $string = implode(', ', $timeComponents);
            if (count($timeComponents) > 1) {
                $string .= ',';
            }
            $string .= ' ' . Craft::t('app', 'and') . ' ';
        } else {
            $string = '';
        }
        $string .= $last;
        return $string;
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

    /**
     * Normalizes and returns a date string along with the format it was set in.
     *
     * @param string $value
     * @return array
     */
    private static function _parseDate(string $value): array
    {
        $value = trim($value);

        // First see if it's in YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.MU formats
        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2}\.\d+)?$/', $value, $match)) {
            $format = 'Y-m-d';
            if (!empty($match[1])) {
                $format .= ' H:i:s.u';
            }
            return [$value, $format];
        }

        // Get the locale's short date format
        $format = Craft::$app->getFormattingLocale()->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP);

        // Make sure it's a 4-digit year
        $format = StringHelper::replace($format, 'y', 'Y');

        // Valid separators are either '-', '.' or '/'.
        if (StringHelper::contains($format, '.')) {
            $separator = '.';
        } elseif (StringHelper::contains($format, '-')) {
            $separator = '-';
        } else {
            $separator = '/';
        }

        // Ensure that the submitted date is using the locale’s separator
        $date = strtr($value, '-./', str_repeat($separator, 3));

        // Two-digit year?
        $altFormat = StringHelper::replace($format, 'Y', 'y');
        if (DateTime::createFromFormat($altFormat, $date) !== false) {
            return [$date, $altFormat];
        }

        return [$date, $format];
    }

    /**
     * Normalizes and returns a time string along with the format it was set in
     *
     * @param string $value
     * @return array
     */
    private static function _parseTime(string $value): array
    {
        $value = trim($value);

        // First see if it's in HH:MM format
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value, $matches)) {
            return [$value, 'H:i' . (isset($matches[1]) ? ':s' : '')];
        }

        // Get the formatting locale's short time format
        $formattingLocale = Craft::$app->getFormattingLocale();
        $format = $formattingLocale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP);

        // Replace the localized "AM" and "PM"
        $am = $formattingLocale->getAMName();
        $pm = $formattingLocale->getPMName();

        if (preg_match('/(.*)(' . preg_quote($am, '/') . '|' . preg_quote($pm, '/') . ')(.*)/iu', $value, $matches)) {
            $value = $matches[1] . $matches[3];

            if (mb_strtolower($matches[2]) === mb_strtolower($am)) {
                $value .= 'AM';
            } else {
                $value .= 'PM';
            }

            $format = str_replace('A', '', $format) . 'A';
        }

        return [$value, $format];
    }

    /**
     * @param string $value
     * @param string $defaultTimeZone
     * @return DateTime|null
     */
    private static function _parseDateTime(string $value, string $defaultTimeZone): ?DateTime
    {
        $value = trim($value);

        $date = match (strtolower($value)) {
            'now' => static::now(),
            'today' => static::today(),
            'tomorrow' => static::tomorrow(),
            'yesterday' => static::yesterday(),
            default => null,
        };

        if ($date !== null) {
            return $date;
        }

        if (preg_match('/^
                (?P<year>\d{4})                                  # YYYY (four digit year)
                (?:
                    -(?P<mon>\d\d?)                              # -M or -MM (1 or 2 digit month)
                    (?:
                        -(?P<day>\d\d?)                          # -D or -DD (1 or 2 digit day)
                        (?:
                            [T\ ](?P<hour>\d\d?)\:(?P<min>\d\d)  # [T or space]hh:mm (1 or 2 digit hour and 2 digit minute)
                            (?:
                                \:(?P<sec>\d\d)                  # :ss (two digit second)
                                (?:\.\d+)?                       # .s (decimal fraction of a second -- not supported)
                            )?
                            (?:[ ]?(?P<ampm>(AM|PM|am|pm))?)?    # An optional space and AM or PM
                            (?P<tz>Z|(?P<tzd>[+\-]\d\d\:?\d\d))? # Z or [+ or -]hh(:)ss (UTC or a timezone offset)
                        )?
                    )?
                )?$/x', $value, $m)) {
            $format = 'Y-m-d H:i:s';

            $date = $m['year'] .
                '-' . (!empty($m['mon']) ? sprintf('%02d', $m['mon']) : '01') .
                '-' . (!empty($m['day']) ? sprintf('%02d', $m['day']) : '01') .
                ' ' . (!empty($m['hour']) ? sprintf('%02d', $m['hour']) : '00') .
                ':' . (!empty($m['min']) ? $m['min'] : '00') .
                ':' . (!empty($m['sec']) ? $m['sec'] : '00');

            if (!empty($m['ampm'])) {
                $format .= ' A';
                $date .= ' ' . $m['ampm'];
            }

            // Did they specify a timezone?
            if (!empty($m['tz'])) {
                if (!empty($m['tzd'])) {
                    $format .= str_contains($m['tzd'], ':') ? 'P' : 'O';
                    $date .= $m['tzd'];
                } else {
                    // "Z" = UTC
                    $format .= 'e';
                    $date .= 'UTC';
                }
            } else {
                $format .= 'e';
                $date .= $defaultTimeZone;
            }

            return DateTime::createFromFormat("!$format", $date) ?: null;
        }

        // This must go after the preg_match(), b/c isValidTimeStamp() will return true for years ("2021")
        if (static::isValidTimeStamp($value)) {
            return new DateTime("@$value");
        }

        return null;
    }

    /**
     * Returns the index of the first day of the week (0-6), according to the user’s preferences.
     *
     * @return int
     * @since 4.3.0
     */
    public static function firstWeekDay(): int
    {
        $user = Craft::$app->getUser()->getIdentity();
        return (int)(($user?->getPreference('weekStartDay')) ?? Craft::$app->getConfig()->getGeneral()->defaultWeekStartDay);
    }
}
