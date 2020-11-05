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
use yii\base\ErrorException;

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
    const SECONDS_MINUTE = 60;

    /**
     * @var int Number of seconds in an hour.
     */
    const SECONDS_HOUR = 3600;

    /**
     * @var int Number of seconds in a day.
     */
    const SECONDS_DAY = 86400;

    /**
     * @var int The number of seconds in a month.
     *
     * Based on a 30.4368 day month, with the product rounded.
     */
    const SECONDS_MONTH = 2629740;

    /**
     * @var int The number of seconds in a year.
     *
     * Based on a 365.2416 day year, with the product rounded.
     */
    const SECONDS_YEAR = 31556874;

    /**
     * @var array Translation pairs for [[translateDate()]]
     */
    private static $_translationPairs;

    /**
     * Converts a value into a DateTime object.
     *
     * `$value` can be in the following formats:
     *
     *  - All W3C date and time formats (http://www.w3.org/TR/NOTE-datetime)
     *  - MySQL DATE and DATETIME formats (http://dev.mysql.com/doc/refman/5.1/en/datetime.html)
     *  - Relaxed versions of W3C and MySQL formats (single-digit months, days, and hours)
     *  - Unix timestamps
     *  - An array with at least one of these keys defined: `datetime`, `date`, or `time`. Supported keys include:
     *      - `date` – a date string in `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS.MU` formats or the current locale’s short date format
     *      - `time` – a time string in `HH:MM` or `HH:MM:SS` (24-hour) format or the current locale’s short time format
     *      - `datetime` – A timestamp in any of the non-array formats supported by this method
     *      - `timezone` – A [valid PHP timezone](http://php.net/manual/en/timezones.php). If set, this will override
     *        the assumed timezone per `$assumeSystemTimeZone`.
     *
     * @param string|int|array|null $value The value that should be converted to a DateTime object.
     * @param bool $assumeSystemTimeZone Whether it should be assumed that the value was set in the system timezone if
     * the timezone was not specified. If this is `false`, UTC will be assumed.
     * @param bool $setToSystemTimeZone Whether to set the resulting DateTime object to the system timezone.
     * @return DateTime|false The DateTime object, or `false` if $object could not be converted to one
     * @throws \Exception
     */
    public static function toDateTime($value, bool $assumeSystemTimeZone = false, bool $setToSystemTimeZone = true)
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
                list($date, $format) = self::_parseDateTime($value['datetime'], $timeZone);
                if ($format === false) {
                    return false;
                }
            } else {
                // Did they specify a date?
                if (!empty($value['date'])) {
                    list($date, $format) = self::_parseDate($value['date']);
                } else {
                    // Default to the current date
                    $format = 'Y-m-d';
                    $date = (new DateTime('now', new DateTimeZone($timeZone)))->format($format);
                }

                // Did they specify a time?
                if (!empty($value['time'])) {
                    list($time, $timeFormat) = self::_parseTime($value['time']);
                    $format .= ' ' . $timeFormat;
                    $date .= ' ' . $time;
                }

                // Add the timezone
                $format .= ' e';
                $date .= ' ' . $timeZone;
            }
        } else {
            list($date, $format) = self::_parseDateTime($value, $defaultTimeZone);
            if ($format === false) {
                return false;
            }
        }

        $dt = DateTime::createFromFormat('!' . $format, $date);

        if ($dt !== false && $setToSystemTimeZone) {
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
    public static function normalizeTimeZone(string $timeZone)
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
            $format = strpos($timeZone, ':') !== false ? 'e' : 'O';
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
     * [DateTime::ATOM](http://php.net/manual/en/class.datetime.php#datetime.constants.atom) or
     * [DateTime::ISO8601](http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601) (with or without
     * the colon between the hours and minutes of the timezone).
     *
     * @param mixed $value The timestamp to check
     * @return bool Whether the value is an ISO-8601 date string
     */
    public static function isIso8601($value): bool
    {
        return is_string($value) && preg_match('/^\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d[\+\-]\d\d\:?\d\d$/', $value);
    }

    /**
     * Converts a date to an ISO-8601 string.
     *
     * @param mixed $date The date, in any format that [[toDateTime()]] supports.
     * @return string|false The date formatted as an ISO-8601 string, or `false` if $date was not a valid date
     */
    public static function toIso8601($date)
    {
        $date = static::toDateTime($date);

        if ($date !== false) {
            return $date->format(DateTime::ATOM);
        }

        return false;
    }

    /**
     * @return DateTime
     */
    public static function currentUTCDateTime(): DateTime
    {
        return new DateTime(null, new DateTimeZone('UTC'));
    }

    /**
     * @return int
     */
    public static function currentTimeStamp(): int
    {
        $date = static::currentUTCDateTime();

        return $date->getTimestamp();
    }

    /**
     * Translates the words in a formatted date string to the application’s language.
     *
     * @param string $str The formatted date string
     * @param string|null $language The language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string The translated date string
     * @deprecated in 3.0.6. Use [[\craft\i18n\Formatter::asDate()]] instead.
     */
    public static function translateDate(string $str, string $language = null): string
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`' . __METHOD__ . '` is deprecated. Use `craft\i18n\Formatter::asDate()` instead.');

        if ($language === null) {
            $language = Craft::$app->language;
        }

        if (strpos($language, 'en') === 0) {
            return $str;
        }

        $translations = self::_getDateTranslations($language);

        return strtr($str, $translations);
    }

    /**
     * @param int $seconds The number of seconds
     * @param bool $showSeconds Whether to output seconds or not
     * @return string
     */
    public static function secondsToHumanTimeDuration(int $seconds, bool $showSeconds = true): string
    {
        $secondsInWeek = 604800;
        $secondsInDay = 86400;
        $secondsInHour = 3600;
        $secondsInMinute = 60;

        $weeks = floor($seconds / $secondsInWeek);
        $seconds %= $secondsInWeek;

        $days = floor($seconds / $secondsInDay);
        $seconds %= $secondsInDay;

        $hours = floor($seconds / $secondsInHour);
        $seconds %= $secondsInHour;

        if ($showSeconds) {
            $minutes = floor($seconds / $secondsInMinute);
            $seconds %= $secondsInMinute;
        } else {
            $minutes = round($seconds / $secondsInMinute);
            $seconds = 0;
        }

        $timeComponents = [];

        if ($weeks) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{week} other{weeks}}', ['num' => $weeks]);
        }

        if ($days) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{day} other{days}}', ['num' => $days]);
        }

        if ($hours) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{hour} other{hours}}', ['num' => $hours]);
        }

        if ($minutes || (!$showSeconds && !$weeks && !$days && !$hours)) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{minute} other{minutes}}', ['num' => $minutes]);
        }

        if ($seconds || ($showSeconds && !$weeks && !$days && !$hours && !$minutes)) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{second} other{seconds}}', ['num' => $seconds]);
        }

        return implode(', ', $timeComponents);
    }

    /**
     * @param string|int $timestamp
     * @return bool
     */
    public static function isValidTimeStamp($timestamp): bool
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
    public static function isToday($date): bool
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('Y-m-d') == $now->format('Y-m-d');
    }

    /**
     * Returns true if given date was yesterday
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date was yesterday, false otherwise.
     */
    public static function isYesterday($date): bool
    {
        $date = self::toDateTime($date);
        $yesterday = new DateTime('yesterday', new DateTimeZone(Craft::$app->getTimeZone()));

        return $date->format('Y-m-d') == $yesterday->format('Y-m-d');
    }

    /**
     * Returns true if given date is in this year
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date is in this year, false otherwise.
     */
    public static function isThisYear($date): bool
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('Y') == $now->format('Y');
    }

    /**
     * Returns true if given date is in this week
     *
     * @param mixed $date The timestamp to check
     * @return bool true if date is in this week, false otherwise.
     */
    public static function isThisWeek($date): bool
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('W Y') == $now->format('W Y');
    }

    /**
     * Returns true if given date is in this month
     *
     * @param mixed $date The timestamp to check
     * @return bool True if date is in this month, false otherwise.
     */
    public static function isThisMonth($date): bool
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('m Y') == $now->format('m Y');
    }

    /**
     * Returns true if specified datetime was within the interval specified, else false.
     *
     * @param mixed $date The timestamp to check
     * @param mixed $timeInterval The numeric value with space then time type.
     * Example of valid types: '6 hours', '2 days', '1 minute'.
     * @return bool Whether the $dateString was within the specified $timeInterval.
     */
    public static function isWithinLast($date, $timeInterval): bool
    {
        if (is_numeric($timeInterval)) {
            $timeInterval .= ' days';
        }

        $date = self::toDateTime($date);
        $timestamp = $date->getTimestamp();

        // Bail early if it's in the future
        if ($timestamp > time()) {
            return false;
        }

        $earliestTimestamp = strtotime('-' . $timeInterval);

        return ($timestamp >= $earliestTimestamp);
    }

    /**
     * Returns true if the specified date was in the past, otherwise false.
     *
     * @param mixed $date The timestamp to check
     * @return bool true if the specified date was in the past, false otherwise.
     */
    public static function isInThePast($date): bool
    {
        $date = self::toDateTime($date);

        return $date->getTimestamp() < time();
    }

    /**
     * Creates a DateInterval object based on a given number of seconds.
     *
     * @param int $seconds
     * @return DateInterval
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
        } catch (ErrorException $e) {
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
     */
    public static function humanDurationFromInterval(DateInterval $dateInterval, bool $showSeconds = true): string
    {
        $timeComponents = [];

        if ($dateInterval->y) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{year} other{years}}', ['num' => $dateInterval->y]);
        }

        if ($dateInterval->m) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{month} other{months}}', ['num' => $dateInterval->m]);
        }

        if ($dateInterval->d) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{day} other{days}}', ['num' => $dateInterval->d]);
        }

        if ($dateInterval->h) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{hour} other{hours}}', ['num' => $dateInterval->h]);
        }

        $minutes = $dateInterval->i;

        if (!$showSeconds) {
            if ($minutes && round($dateInterval->s / 60)) {
                $minutes++;
            } else if (!$dateInterval->y && !$dateInterval->m && !$dateInterval->d && !$dateInterval->h && !$minutes) {
                return Craft::t('app', 'less than a minute');
            }
        }

        if ($minutes) {
            $timeComponents[] = Craft::t('app', '{num, number} {num, plural, =1{minute} other{minutes}}', ['num' => $minutes]);
        }

        if ($showSeconds && $dateInterval->s) {
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
        $format = Craft::$app->getLocale()->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP);

        // Make sure it's a 4-digit year
        $format = StringHelper::replace($format, 'y', 'Y');

        // Valid separators are either '-', '.' or '/'.
        if (StringHelper::contains($format, '.')) {
            $separator = '.';
        } else if (StringHelper::contains($format, '-')) {
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

        // Get the locale's short time format
        $locale = Craft::$app->getLocale();
        $format = $locale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP);

        // Replace the localized "AM" and "PM"
        $am = $locale->getAMName();
        $pm = $locale->getPMName();

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
     * Normalizes and returns a date & time string along with the format it was set in.
     *
     * @param string $value
     * @param string $defaultTimeZone
     * @return array
     */
    private static function _parseDateTime(string $value, string $defaultTimeZone): array
    {
        $value = trim($value);

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
                    $format .= strpos($m['tzd'], ':') !== false ? 'P' : 'O';
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

            return [$date, $format];
        }

        if (static::isValidTimeStamp($value)) {
            return [$value, 'U'];
        }

        return [$value, false];
    }

    /**
     * Returns translation pairs for [[translateDate()]].
     *
     * @param string $language The target language
     * @return array The translation pairs
     */
    private static function _getDateTranslations(string $language): array
    {
        if (!isset(self::$_translationPairs[$language])) {
            if (strpos(Craft::$app->language, 'en') === 0) {
                $sourceLocale = Craft::$app->getLocale();
            } else {
                $sourceLocale = Craft::$app->getI18n()->getLocaleById('en-US');
            }

            $targetLocale = Craft::$app->getI18n()->getLocaleById($language);

            $amName = $targetLocale->getAMName();
            $pmName = $targetLocale->getPMName();

            self::$_translationPairs[$language] = array_merge(
                array_combine($sourceLocale->getMonthNames(Locale::LENGTH_FULL), $targetLocale->getMonthNames(Locale::LENGTH_FULL)),
                array_combine($sourceLocale->getWeekDayNames(Locale::LENGTH_FULL), $targetLocale->getWeekDayNames(Locale::LENGTH_FULL)),
                array_combine($sourceLocale->getMonthNames(Locale::LENGTH_MEDIUM), $targetLocale->getMonthNames(Locale::LENGTH_MEDIUM)),
                array_combine($sourceLocale->getWeekDayNames(Locale::LENGTH_MEDIUM), $targetLocale->getWeekDayNames(Locale::LENGTH_MEDIUM)),
                [
                    'AM' => mb_strtoupper($amName),
                    'PM' => mb_strtoupper($pmName),
                    'am' => mb_strtolower($amName),
                    'pm' => mb_strtolower($pmName)
                ]
            );
        }

        return self::$_translationPairs[$language];
    }
}
