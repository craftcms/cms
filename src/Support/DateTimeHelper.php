<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Carbon\CarbonTimeZone;
use Carbon\Exceptions\InvalidTimeZoneException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Throwable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class DateTimeHelper
{
    public const array RELATIVE_TIME_UNITS = [
        'sec',
        'secs',
        'second',
        'seconds',
        'min',
        'mins',
        'minute',
        'minutes',
        'hour',
        'hours',
        'day',
        'days',
        'fortnight',
        'fortnights',
        'forthnight',
        'forthnights',
        'month',
        'months',
        'year',
        'years',
        'week',
        'weeks',
    ];

    /** @var list<DateTimeInterface|null> */
    private static array $_testNowStack = [];

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
     *      - `date` – A date string in `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS.MU` formats or the current locale's short date format.
     *      - `time` – A time string in `HH:MM` or `HH:MM:SS` (24-hour) format or the current locale's short time format.
     *      - `datetime` – A timestamp in any of the non-array formats supported by this method
     *      - `locale` – The locale ID that the date and time were formatted in. Defaults to the app's current formatting locale.
     *      - `timezone` – A [valid PHP timezone](https://php.net/manual/en/timezones.php). If set, this will override
     *        the assumed timezone per `$assumeSystemTimeZone`.
     *
     * @param  string|int|array<string, mixed>|DateTimeInterface|null  $value  The value that should be converted to a DateTime object.
     * @param  bool  $assumeSystemTimeZone  Whether it should be assumed that the value was set in the system timezone if
     *                                      the timezone was not specified. If this is `false`, UTC will be assumed.
     * @param  bool  $setToSystemTimeZone  Whether to set the resulting DateTime object to the system timezone.
     * @return DateTimeInterface|false The DateTime object, or `false` if $object could not be converted to one
     *
     * @throws Exception
     */
    public static function toDateTime(mixed $value, bool $assumeSystemTimeZone = false, bool $setToSystemTimeZone = true): DateTimeInterface|false
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Date::instance($value);
        }

        if (! $value) {
            return false;
        }

        $defaultTimeZone = $assumeSystemTimeZone ? Cms::timezone() : 'UTC';

        if (is_array($value)) {
            if (empty($value['datetime']) && empty($value['date']) && empty($value['time'])) {
                return false;
            }

            $locale = I18N::getFormattingLocale();

            if (! empty($value['locale']) && $value['locale'] !== $locale->id) {
                $locale = I18N::getLocaleById($value['locale']);
            }

            if (! empty($value['timezone']) && ($normalizedTimeZone = static::normalizeTimeZone($value['timezone'])) !== false) {
                $timeZone = $normalizedTimeZone;
            } else {
                $timeZone = $defaultTimeZone;
            }

            if (! empty($value['datetime'])) {
                $dateTime = static::parseDateTime($value['datetime'], $timeZone);
            } else {
                if (! empty($value['date'])) {
                    [$date, $format] = static::parseDate($value['date'], $locale);
                } else {
                    $format = 'Y-m-d';
                    $date = now($timeZone)->format($format);
                }

                if (! empty($value['time'])) {
                    [$time, $timeFormat] = static::parseTime($value['time'], $locale);
                    $format .= ' '.$timeFormat;
                    $date .= ' '.$time;
                }

                $format .= ' e';
                $date .= ' '.$timeZone;

                $dateTime = self::createFromFormat("!$format", $date);
            }
        } else {
            $dateTime = static::parseDateTime($value, $defaultTimeZone);
        }

        if ($dateTime === null) {
            return false;
        }

        if ($setToSystemTimeZone) {
            return Date::instance($dateTime)->setTimezone(Cms::timezone());
        }

        return $dateTime;
    }

    /**
     * Normalizes a timezone string to a PHP timezone identifier.
     *
     * Supports the following formats:
     *  - Time zone abbreviation (EST, MDT)
     *  - Difference to Greenwich time (GMT) in hours, with/without a colon between the hours and minutes (+0200, -0200, +02:00, -02:00)
     *  - A PHP timezone identifier (UTC, GMT, Atlantic/Azores)
     *
     * @param  string  $timeZone  The timezone to be normalized
     * @return string|false The PHP timezone identifier, or `false` if it could not be determined
     */
    public static function normalizeTimeZone(string $timeZone): string|false
    {
        if (in_array($timeZone, timezone_identifiers_list(), true)) {
            return $timeZone;
        }

        if (($timeZoneName = timezone_name_from_abbr($timeZone)) !== false) {
            return $timeZoneName;
        }

        try {
            return CarbonTimeZone::instance($timeZone)?->getName() ?? false;
        } catch (InvalidTimeZoneException) {
            return false;
        }
    }

    /**
     * Returns the timezone abbreviation for a given timezone name.
     */
    public static function timeZoneAbbreviation(
        string|DateTimeZone $timeZone,
        ?DateTimeInterface $date = null,
    ): string {
        $originalTimeZone = $timeZone;

        if (is_string($timeZone) && ($timeZone = static::normalizeTimeZone($timeZone)) === false) {
            throw new InvalidArgumentException("Invalid time zone: $originalTimeZone");
        }

        $timeZone = CarbonTimeZone::instance($timeZone);

        if ($timeZone->getType() !== 3) {
            return $timeZone->getName();
        }

        return Date::instance($date ?? now('UTC'))
            ->setTimezone($timeZone)
            ->format('T');
    }

    /**
     * Determines whether the given value is an ISO-8601-formatted date, as formatted by either
     * [DateTime::ATOM](https://php.net/manual/en/class.datetime.php#datetime.constants.atom) or
     * [DateTime::ISO8601](https://php.net/manual/en/class.datetime.php#datetime.constants.iso8601) (with or without
     * the colon between the hours and minutes of the timezone).
     *
     * @param  mixed  $value  The timestamp to check
     * @return bool Whether the value is an ISO-8601 date string
     */
    public static function isIso8601(mixed $value): bool
    {
        return is_string($value) && (
            Date::canBeCreatedFromFormat($value, DateTimeInterface::ATOM) ||
            Date::canBeCreatedFromFormat($value, DateTimeInterface::ISO8601)
        );
    }

    /**
     * Converts a date to an ISO-8601 string.
     *
     * @param  mixed  $date  The date, in any format that [[toDateTime()]] supports.
     * @param  bool  $setToUtc  Whether the resulting string should be set to UTC.
     * @return string|false The date formatted as an ISO-8601 string, or `false` if $date was not a valid date
     */
    public static function toIso8601(mixed $date, bool $setToUtc = false): string|false
    {
        if ($date instanceof DateTimeInterface) {
            $date = Date::instance($date);
        } else {
            $date = static::toDateTime($date);

            if (! $date) {
                return false;
            }
        }

        if ($setToUtc) {
            $date = Date::instance($date)->setTimezone('UTC');
        }

        return $date->format(DateTimeInterface::ATOM);
    }

    /**
     * Pauses time for any subsequent calls to other `DateTimeHelper` methods, until [[resume()]] is called.
     *
     * If this method is called multiple times, [[resume()]] will need to be called an equal number of times before
     * time is actually resumed.
     *
     * @param  DateTimeInterface|null  $now  A `DateTime` object that should represent the current time for the duration of the pause
     */
    public static function pause(?DateTimeInterface $now = null): void
    {
        self::$_testNowStack[] = Date::getTestNow();

        Date::setTestNow($now ? Date::instance($now) : now());
    }

    /**
     * Resumes time, if it was paused via [[pause()]].
     */
    public static function resume(): void
    {
        if (empty(self::$_testNowStack)) {
            return;
        }

        Date::setTestNow(array_pop(self::$_testNowStack));
    }

    public static function isValidTimeStamp(mixed $timestamp): bool
    {
        if (! is_numeric($timestamp)) {
            return false;
        }

        $timestamp = (int) $timestamp;

        return $timestamp <= PHP_INT_MAX && $timestamp >= ~PHP_INT_MAX;
    }

    /**
     * Converts a value into a DateInterval object.
     *
     * @param  mixed  $value  The value, represented as either a [[\DateInterval]] object, an interval duration string, or a number of seconds.
     *
     * @throws InvalidArgumentException
     */
    public static function toDateInterval(mixed $value): DateInterval|false
    {
        if ($value instanceof DateInterval) {
            return $value;
        }

        if (! $value) {
            return false;
        }

        if (is_numeric($value)) {
            $value = (int) $value;
            $now = now('UTC');
            $then = Date::instance($now)->addSeconds($value);

            return $now->diff($then);
        }

        if (is_string($value)) {
            try {
                return new DateInterval($value);
            } catch (Exception) {
            }
        }

        throw new InvalidArgumentException('Unable to convert the value to a DateInterval.');
    }

    /**
     * Converts a time to an integer (the number of seconds since midnight).
     */
    public static function timeToSeconds(int|string|DateTimeInterface|null $time): ?int
    {
        if (is_int($time) || $time === null) {
            return $time;
        }

        if (is_string($time)) {
            [$hours, $minutes, $seconds] = array_pad(explode(':', $time), 3, 0);
        } else {
            $hours = (int) $time->format('H');
            $minutes = (int) $time->format('i');
            $seconds = (int) $time->format('s');
        }

        return (int) $hours * 3600 + (int) $minutes * 60 + (int) $seconds;
    }

    /**
     * Returns a human-friendly duration string for the given date interval or number of seconds.
     *
     * @param  mixed  $dateInterval  The value, represented as either a [[\DateInterval]] object, an interval duration string, or a number of seconds.
     * @param  bool|null  $showSeconds  Whether the duration string should include the number of seconds
     * @param  string|null  $language  The language code that should be used. (Defaults to the current application language.)
     */
    public static function humanDuration(mixed $dateInterval, ?bool $showSeconds = null, ?string $language = null): string
    {
        $dateInterval = static::toDateInterval($dateInterval) ?: new DateInterval('PT0S');
        $secondsOnly = ! $dateInterval->y && ! $dateInterval->m && ! $dateInterval->d && ! $dateInterval->h && ! $dateInterval->i;

        $showSeconds ??= $secondsOnly;

        $timeComponents = [];

        if ($dateInterval->y) {
            $timeComponents[] = t('{num, number} {num, plural, =1{year} other{years}}', [
                'num' => $dateInterval->y,
            ], locale: $language);
        }

        if ($dateInterval->m) {
            $timeComponents[] = t('{num, number} {num, plural, =1{month} other{months}}', [
                'num' => $dateInterval->m,
            ], locale: $language);
        }

        if ($dateInterval->d) {
            if ($dateInterval->d % 7 === 0) {
                $timeComponents[] = t('{num, number} {num, plural, =1{week} other{weeks}}', [
                    'num' => $dateInterval->d / 7,
                ], locale: $language);
            } else {
                $timeComponents[] = t('{num, number} {num, plural, =1{day} other{days}}', [
                    'num' => $dateInterval->d,
                ], locale: $language);
            }
        }

        if ($dateInterval->h) {
            $timeComponents[] = t('{num, number} {num, plural, =1{hour} other{hours}}', [
                'num' => $dateInterval->h,
            ], locale: $language);
        }

        $minutes = $dateInterval->i;

        if (! $showSeconds) {
            $additionalMinutes = round($dateInterval->s / 60);

            if ($additionalMinutes) {
                $minutes += $additionalMinutes;
            } elseif ($secondsOnly) {
                return t('less than a minute');
            }
        }

        if ($minutes) {
            $timeComponents[] = t('{num, number} {num, plural, =1{minute} other{minutes}}', [
                'num' => $minutes,
            ], locale: $language);
        }

        if ($showSeconds && ($dateInterval->s || empty($timeComponents))) {
            $timeComponents[] = t('{num, number} {num, plural, =1{second} other{seconds}}', [
                'num' => $dateInterval->s,
            ], locale: $language);
        }

        $last = array_pop($timeComponents);

        if (empty($timeComponents)) {
            return $last;
        }

        $string = implode(', ', $timeComponents);

        if (count($timeComponents) > 1) {
            $string .= ',';
        }

        $string .= ' '.t('and', locale: $language).' ';

        return $string.$last;
    }

    /**
     * Returns the index of the first day of the week (0-6), according to the user's preferences.
     */
    public static function firstWeekDay(): int
    {
        $user = currentUser();

        return (int) ($user?->getPreference('weekStartDay') ?? Cms::config()->defaultWeekStartDay);
    }

    /**
     * Normalizes and returns a date string along with the format it was set in.
     *
     * @return array{string, string}
     */
    protected static function parseDate(string $value, Locale $locale): array
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2}(\.\d+)?)?$/', $value, $match)) {
            $format = 'Y-m-d';

            if (! empty($match[1])) {
                $format .= ' H:i:s';

                if (! empty($match[2])) {
                    $format .= '.u';
                }
            }

            return [$value, $format];
        }

        $format = $locale->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP);
        $format = str_replace('y', 'Y', $format);

        $separator = match (true) {
            str_contains($format, '.') => '.',
            str_contains($format, '-') => '-',
            default => '/',
        };

        $date = strtr($value, '-./', str_repeat($separator, 3));
        $alternateFormat = str_replace('Y', 'y', $format);

        if (self::createFromFormat($alternateFormat, $date) !== null) {
            return [$date, $alternateFormat];
        }

        return [$date, $format];
    }

    /**
     * Normalizes and returns a time string along with the format it was set in.
     *
     * @return array{string, string}
     */
    protected static function parseTime(string $value, Locale $locale): array
    {
        $value = str_replace("\u{202f}", ' ', trim($value));

        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value, $matches)) {
            return [$value, 'H:i'.(isset($matches[1]) ? ':s' : '')];
        }

        foreach ([
            'AM' => $locale->getAMName(),
            'PM' => $locale->getPMName(),
        ] as $period => $name) {
            $matches = array_filter(array_unique([$name, preg_replace('/[\s.]/', '', $name)]));

            foreach ($matches as $match) {
                $quoted = preg_quote((string) $match, '/');

                if (preg_match("/(.*)$quoted(.*)/iu", $value, $parts)) {
                    $value = "$parts[1]$parts[2]$period";

                    break 2;
                }
            }
        }

        try {
            return [Date::parse($value)->format('H:i:s'), 'H:i:s'];
        } catch (Throwable) {
            return [$value, str_replace("\u{202f}", ' ', $locale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP))];
        }
    }

    protected static function parseDateTime(mixed $value, string $defaultTimeZone): ?DateTime
    {
        $value = trim((string) $value);

        if (preg_match('/^\d{4}$/', $value)) {
            return self::createFromFormat('!Y-m-d H:i:s e', "$value-01-01 00:00:00 $defaultTimeZone");
        }

        if (static::isValidTimeStamp($value)) {
            return Date::createFromTimestampUTC($value);
        }

        try {
            $date = Date::parse(
                $value,
                preg_match('/^\d{4}-\d\d?(?:-\d\d?)?(?:[T ]|$)/', $value) ? $defaultTimeZone : null,
            );

            if (! str_contains($value, '/') && preg_match('/([a-zA-Z]{1,5})$/', $value, $matches)) {
                $timeZone = strcasecmp($matches[1], 'Z') === 0 ? 'UTC' : static::normalizeTimeZone($matches[1]);

                if ($timeZone !== false) {
                    $date->setTimezone($timeZone);
                }
            }

            return $date;
        } catch (Throwable) {
            return null;
        }
    }

    private static function createFromFormat(string $format, string $date, DateTimeZone|string|int|null $timezone = null): ?DateTime
    {
        try {
            return Date::createFromFormat($format, $date, $timezone);
        } catch (Throwable) {
            return null;
        }
    }
}
