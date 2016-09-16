<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\dates\DateInterval;
use craft\app\dates\DateTime;
use craft\app\i18n\Locale;
use yii\helpers\FormatConverter;

/**
 * Class DateTimeHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DateTimeHelper
{
    // Properties
    // =========================================================================

    /**
     * @var array Translation pairs for [[translateDate()]]
     */
    private static $_translationPairs;

    // Public Methods
    // =========================================================================

    /**
     * Converts a value into a DateTime object.
     *
     * Supports the following formats:
     *
     *  - An array of the date and time in the current locale's short formats
     *  - All W3C date and time formats (http://www.w3.org/TR/NOTE-datetime)
     *  - MySQL DATE and DATETIME formats (http://dev.mysql.com/doc/refman/5.1/en/datetime.html)
     *  - Relaxed versions of W3C and MySQL formats (single-digit months, days, and hours)
     *  - Unix timestamps
     *
     * @param mixed   $value                The value that should be converted to a DateTime object.
     * @param boolean $assumeSystemTimeZone Whether it should be assumed that the value was set in the system time zone if the timezone was not specified. If this is false, UTC will be assumed. (Defaults to false.)
     * @param boolean $setToSystemTimeZone  Whether to set the resulting DateTime object to the system time zone. (Defaults to true.)
     *
     * @return DateTime|false The DateTime object, or `false` if $object could not be converted to one
     */
    public static function toDateTime($value, $assumeSystemTimeZone = false, $setToSystemTimeZone = true)
    {
        if ($value instanceof \DateTime) {
            // Make sure it's a Craft DateTime object
            if (!($value instanceof DateTime)) {
                return new DateTime('@'.$value->getTimestamp());
            }

            return $value;
        }

        $defaultTimeZone = ($assumeSystemTimeZone ? Craft::$app->getTimeZone() : 'UTC');

        // Was this a date/time-picker?
        if (is_array($value) && (isset($value['date']) || isset($value['time']))) {
            $dt = $value;

            if (empty($dt['date']) && empty($dt['time'])) {
                return false;
            }

            $locale = Craft::$app->getLocale();

            if (!empty($value['timezone']) && ($normalizedTimeZone = static::normalizeTimeZone($value['timezone'])) !== false) {
                $timeZone = $normalizedTimeZone;
            } else {
                $timeZone = $defaultTimeZone;
            }

            if (!empty($dt['date'])) {
                $date = $dt['date'];
                $format = FormatConverter::convertDateIcuToPhp('short', 'date', $locale->id);

                // Make sure it's a 4 digit year format.
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
                $date = StringHelper::replace($date, '-', $separator);
                $date = StringHelper::replace($date, '.', $separator);
                $date = StringHelper::replace($date, '/', $separator);

                // Check for a two-digit year as well
                $altFormat = StringHelper::replace($format, 'Y', 'y');

                if (DateTime::createFromFormat($altFormat, $date) !== false) {
                    $format = $altFormat;
                }
            } else {
                $date = '';
                $format = '';

                // Default to the current date
                $current = new DateTime('now', new \DateTimeZone($timeZone));
                $date .= $current->month().'/'.$current->day().'/'.$current->year();
                $format .= 'n/j/Y';
            }

            if (!empty($dt['time'])) {
                $timePickerPhpFormat = FormatConverter::convertDateIcuToPhp('short', 'time', $locale->id);
                // Replace the localized "AM" and "PM"
                if (preg_match('/(.*)('.preg_quote($locale->getAMName(), '/').'|'.preg_quote($locale->getPMName(), '/').')(.*)/u', $dt['time'], $matches)) {
                    $dt['time'] = $matches[1].$matches[3];

                    if ($matches[2] == $locale->getAMName()) {
                        $dt['time'] .= 'AM';
                    } else {
                        $dt['time'] .= 'PM';
                    }

                    $timePickerPhpFormat = str_replace('A', '', $timePickerPhpFormat).'A';
                }

                $date .= ' '.$dt['time'];
                $format .= ' '.$timePickerPhpFormat;
            }

            // Add the timezone
            $format .= ' e';
            $date .= ' '.$timeZone;
        } else {
            $date = trim((string)$value);

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
                )?$/x', $date, $m)) {
                $format = 'Y-m-d H:i:s';

                $date = $m['year'].
                    '-'.(!empty($m['mon']) ? sprintf('%02d', $m['mon']) : '01').
                    '-'.(!empty($m['day']) ? sprintf('%02d', $m['day']) : '01').
                    ' '.(!empty($m['hour']) ? sprintf('%02d', $m['hour']) : '00').
                    ':'.(!empty($m['min']) ? $m['min'] : '00').
                    ':'.(!empty($m['sec']) ? $m['sec'] : '00');

                if (!empty($m['ampm'])) {
                    $format .= ' A';
                    $date .= ' '.$m['ampm'];
                }

                // Was a time zone specified?
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
            } else if (static::isValidTimeStamp((int)$date)) {
                $format = 'U';
            } else {
                return false;
            }
        }

        $dt = DateTime::createFromFormat('!'.$format, $date);

        if ($dt !== false && $setToSystemTimeZone) {
            $dt->setTimezone(new \DateTimeZone(Craft::$app->getTimeZone()));
        }

        return $dt;
    }

    /**
     * Normalizes a time zone string to a PHP time zone identifier.
     *
     * Supports the following formats:
     *
     *  - Time zone abbreviation (EST, MDT)
     *  - Difference to Greenwich time (GMT) in hours, with/without a colon between the hours and minutes (+0200, -0200, +02:00, -02:00)
     *  - A PHP time zone identifier (UTC, GMT, Atlantic/Azores)
     *
     * @param string $timeZone The time zone to be normalized
     *
     * @return string|false The PHP time zone identifier, or `false` if it could not be determined
     */
    public static function normalizeTimeZone($timeZone)
    {
        // Is it already a PHP time zone identifier?
        if (in_array($timeZone, timezone_identifiers_list())) {
            return $timeZone;
        }

        // Is this a time zone abbreviation?
        if (($timeZoneName = timezone_name_from_abbr($timeZone)) !== false) {
            return $timeZoneName;
        }

        // Is it the difference to GMT?
        if (preg_match('/[+\-]\d\d\:?\d\d/', $timeZone, $matches)) {
            $format = strpos($timeZone, ':') !== false ? 'e' : 'O';
            $dt = \DateTime::createFromFormat($format, $timeZone, new \DateTimeZone('UTC'));

            if ($dt !== false) {
                return $dt->format('e');
            }
        }

        // Dunno
        return false;
    }

    /**
     * Determines whether the given value is an ISO-8601-formatted date, as formatted by either
     * [DateTime::ATOM](http://php.net/manual/en/class.datetime.php#datetime.constants.atom) or
     * [DateTime::ISO8601](http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601) (with or without
     * the colon between the hours and minutes of the timezone).
     *
     * @param mixed $value The timestamp to check
     *
     * @return boolean Whether the value is an ISO-8601 date string
     */
    public static function isIso8601($value)
    {
        if (is_string($value) && preg_match('/^\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d[\+\-]\d\d\:?\d\d$/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Converts a date to an ISO-8601 string.
     *
     * @param mixed $date The date, in any format that [[toDateTime()]] supports.
     *
     * @return string|false The date formatted as an ISO-8601 string, or `false` if $date was not a valid date
     */
    public static function toIso8601($date)
    {
        $date = static::toDateTime($date);

        if ($date !== false) {
            return $date->format(\DateTime::ATOM);
        }

        return false;
    }

    /**
     * @return DateTime
     */
    public static function currentUTCDateTime()
    {
        return new DateTime(null, new \DateTimeZone('UTC'));
    }

    /**
     * @return integer
     */
    public static function currentTimeStamp()
    {
        $date = static::currentUTCDateTime();

        return $date->getTimestamp();
    }

    /**
     * Translates the words in a formatted date string to the application’s language.
     *
     * @param string $str      The formatted date string
     * @param string $language The language code (e.g. `en-US`, `en`). If this is null, the current
     *                         [[\yii\base\Application::language|application language]] will be used.
     *
     * @return string The translated date string
     */
    public static function translateDate($str, $language = null)
    {
        if ($language === null) {
            $language = Craft::$app->language;
        }

        if (strncmp($language, 'en', 2) === 0) {
            return $str;
        }

        $translations = self::_getDateTranslations($language);

        return strtr($str, $translations);
    }

    /**
     * @param integer $seconds     The number of seconds
     * @param boolean $showSeconds Whether to output seconds or not
     *
     * @return string
     */
    public static function secondsToHumanTimeDuration($seconds, $showSeconds = true)
    {
        $secondsInWeek = 604800;
        $secondsInDay = 86400;
        $secondsInHour = 3600;
        $secondsInMinute = 60;

        $weeks = floor($seconds / $secondsInWeek);
        $seconds = $seconds % $secondsInWeek;

        $days = floor($seconds / $secondsInDay);
        $seconds = $seconds % $secondsInDay;

        $hours = floor($seconds / $secondsInHour);
        $seconds = $seconds % $secondsInHour;

        if ($showSeconds) {
            $minutes = floor($seconds / $secondsInMinute);
            $seconds = $seconds % $secondsInMinute;
        } else {
            $minutes = round($seconds / $secondsInMinute);
            $seconds = 0;
        }

        $timeComponents = [];

        if ($weeks) {
            $timeComponents[] = $weeks.' '.($weeks == 1 ? Craft::t('app',
                    'week') : Craft::t('app', 'weeks'));
        }

        if ($days) {
            $timeComponents[] = $days.' '.($days == 1 ? Craft::t('app',
                    'day') : Craft::t('app', 'days'));
        }

        if ($hours) {
            $timeComponents[] = $hours.' '.($hours == 1 ? Craft::t('app',
                    'hour') : Craft::t('app', 'hours'));
        }

        if ($minutes || (!$showSeconds && !$weeks && !$days && !$hours)) {
            $timeComponents[] = $minutes.' '.($minutes == 1 ? Craft::t('app',
                    'minute') : Craft::t('app', 'minutes'));
        }

        if ($seconds || ($showSeconds && !$weeks && !$days && !$hours && !$minutes)) {
            $timeComponents[] = $seconds.' '.($seconds == 1 ? Craft::t('app',
                    'second') : Craft::t('app', 'seconds'));
        }

        return implode(', ', $timeComponents);
    }

    /**
     * @param $timestamp
     *
     * @return boolean
     */
    public static function isValidTimeStamp($timestamp)
    {
        return (is_numeric($timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX));
    }

    /**
     * Returns true if given date is today.
     *
     * @param mixed $date The timestamp to check
     *
     * @return boolean true if date is today, false otherwise.
     */
    public static function isToday($date)
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('Y-m-d') == $now->format('Y-m-d');
    }

    /**
     * Returns true if given date was yesterday
     *
     * @param mixed $date The timestamp to check
     *
     * @return boolean true if date was yesterday, false otherwise.
     */
    public static function wasYesterday($date)
    {
        $date = self::toDateTime($date);
        $yesterday = new DateTime('@'.strtotime('yesterday'));

        return $date->format('Y-m-d') == $yesterday->format('Y-m-d');
    }

    /**
     * Returns true if given date is in this year
     *
     * @param mixed $date The timestamp to check
     *
     * @return boolean true if date is in this year, false otherwise.
     */
    public static function isThisYear($date)
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('Y') == $now->format('Y');
    }

    /**
     * Returns true if given date is in this week
     *
     * @param mixed $date The timestamp to check
     *
     * @return boolean true if date is in this week, false otherwise.
     */
    public static function isThisWeek($date)
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('W Y') == $now->format('W Y');
    }

    /**
     * Returns true if given date is in this month
     *
     * @param mixed $date The timestamp to check
     *
     * @return boolean True if date is in this month, false otherwise.
     */
    public static function isThisMonth($date)
    {
        $date = self::toDateTime($date);
        $now = new DateTime();

        return $date->format('m Y') == $now->format('m Y');
    }

    /**
     * Returns true if specified datetime was within the interval specified, else false.
     *
     * @param mixed $timeInterval   The numeric value with space then time type. Example of valid types: 6 hours, 2 days,
     *                              1 minute.
     * @param mixed $date           The timestamp to check
     *
     * @return boolean Whether the $dateString was within the specified $timeInterval.
     */
    public static function wasWithinLast($timeInterval, $date)
    {
        if (is_numeric($timeInterval)) {
            $timeInterval = $timeInterval.' days';
        }

        $date = self::toDateTime($date);
        $timestamp = $date->getTimestamp();

        // Bail early if it's in the future
        if ($timestamp > time()) {
            return false;
        }

        $earliestTimestamp = strtotime('-'.$timeInterval);

        return ($timestamp >= $earliestTimestamp);
    }

    /**
     * Returns true if the specified date was in the past, otherwise false.
     *
     * @param mixed $date The timestamp to check
     *
     * @return boolean true if the specified date was in the past, false otherwise.
     */
    public static function wasInThePast($date)
    {
        $date = self::toDateTime($date);

        return $date->getTimestamp() < time();
    }

    /**
     * Returns either a relative date or a formatted date depending on the difference between the current time and given
     * datetime. $datetime should be in a **strtotime**-parsable format, like MySQL's
     * datetime datatype.
     *
     * Options:
     *  * 'format' => a fall back format if the relative time is longer than the duration specified by end
     *  * 'end' =>  The end of relative time telling
     *
     * Relative dates look something like this:
     *  3 weeks, 4 days ago
     *  15 seconds ago
     * Formatted dates look like this:
     *  on 02/18/2004
     *
     * The returned string includes 'ago' or 'on' and assumes you'll properly add a word  like 'Posted ' before the
     * function output.
     *
     * @param mixed $date    The timestamp to check
     * @param array $options Default format if timestamp is used in $dateString
     *
     * @return string The relative time string.
     */
    public static function timeAgoInWords($date, $options = [])
    {
        $now = time();

        $date = self::toDateTime($date);
        $inSeconds = $date->getTimestamp();
        $backwards = ($inSeconds > $now);

        $format = 'j/n/y';
        $end = '+1 month';

        if (is_array($options)) {
            if (isset($options['format'])) {
                $format = $options['format'];
                unset($options['format']);
            }
            if (isset($options['end'])) {
                $end = $options['end'];
                unset($options['end']);
            }
        } else {
            $format = $options;
        }

        if ($backwards) {
            $futureTime = $inSeconds;
            $pastTime = $now;
        } else {
            $futureTime = $now;
            $pastTime = $inSeconds;
        }

        $diff = $futureTime - $pastTime;

        // If more than a week, then take into account the length of months
        if ($diff >= 604800) {
            list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));
            list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));

            $years = $months = $weeks = $days = $hours = $minutes = $seconds = 0;

            if ($future['Y'] == $past['Y'] && $future['m'] == $past['m']) {
                $months = 0;
                $years = 0;
            } else {
                if ($future['Y'] == $past['Y']) {
                    $months = $future['m'] - $past['m'];
                } else {
                    $years = $future['Y'] - $past['Y'];
                    $months = $future['m'] + ((12 * $years) - $past['m']);

                    if ($months >= 12) {
                        $years = floor($months / 12);
                        $months = $months - ($years * 12);
                    }

                    if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] == 1) {
                        $years--;
                    }
                }
            }

            if ($future['d'] >= $past['d']) {
                $days = $future['d'] - $past['d'];
            } else {
                $daysInPastMonth = date('t', $pastTime);
                $daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

                if (!$backwards) {
                    $days = ($daysInPastMonth - $past['d']) + $future['d'];
                } else {
                    $days = ($daysInFutureMonth - $past['d']) + $future['d'];
                }

                if ($future['m'] != $past['m']) {
                    $months--;
                }
            }

            if ($months == 0 && $years >= 1 && $diff < ($years * 31536000)) {
                $months = 11;
                $years--;
            }

            if ($months >= 12) {
                $years = $years + 1;
                $months = $months - 12;
            }

            if ($days >= 7) {
                $weeks = floor($days / 7);
                $days = $days - ($weeks * 7);
            }
        } else {
            $years = $months = $weeks = 0;
            $days = floor($diff / 86400);

            $diff = $diff - ($days * 86400);

            $hours = floor($diff / 3600);
            $diff = $diff - ($hours * 3600);

            $minutes = floor($diff / 60);
            $diff = $diff - ($minutes * 60);
            $seconds = $diff;
        }

        $relativeDate = '';
        $diff = $futureTime - $pastTime;

        if ($diff > abs($now - strtotime($end))) {
            $relativeDate = sprintf('on %s', date($format, $inSeconds));
        } else {
            if ($years > 0) {
                // years and months and days
                $relativeDate .= ($relativeDate ? ', ' : '').$years.' '.($years == 1 ? 'year' : 'years');
                $relativeDate .= $months > 0 ? ($relativeDate ? ', ' : '').$months.' '.($months == 1 ? 'month' : 'months') : '';
                $relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '').$weeks.' '.($weeks == 1 ? 'week' : 'weeks') : '';
                $relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days') : '';
            } else if (abs($months) > 0) {
                // months, weeks and days
                $relativeDate .= ($relativeDate ? ', ' : '').$months.' '.($months == 1 ? 'month' : 'months');
                $relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '').$weeks.' '.($weeks == 1 ? 'week' : 'weeks') : '';
                $relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days') : '';
            } else if (abs($weeks) > 0) {
                // weeks and days
                $relativeDate .= ($relativeDate ? ', ' : '').$weeks.' '.($weeks == 1 ? 'week' : 'weeks');
                $relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days') : '';
            } else if (abs($days) > 0) {
                // days and hours
                $relativeDate .= ($relativeDate ? ', ' : '').$days.' '.($days == 1 ? 'day' : 'days');
                $relativeDate .= $hours > 0 ? ($relativeDate ? ', ' : '').$hours.' '.($hours == 1 ? 'hour' : 'hours') : '';
            } else if (abs($hours) > 0) {
                // hours and minutes
                $relativeDate .= ($relativeDate ? ', ' : '').$hours.' '.($hours == 1 ? 'hour' : 'hours');
                $relativeDate .= $minutes > 0 ? ($relativeDate ? ', ' : '').$minutes.' '.($minutes == 1 ? 'minute' : 'minutes') : '';
            } else if (abs($minutes) > 0) {
                // minutes only
                $relativeDate .= ($relativeDate ? ', ' : '').$minutes.' '.($minutes == 1 ? 'minute' : 'minutes');
            } else {
                // seconds only
                $relativeDate .= ($relativeDate ? ', ' : '').$seconds.' '.($seconds == 1 ? 'second' : 'seconds');
            }

            if (!$backwards) {
                $relativeDate = sprintf('%s ago', $relativeDate);
            }
        }

        return $relativeDate;
    }

    /**
     * Takes a PHP time format string and converts it to seconds.
     * {@see http://www.php.net/manual/en/datetime.formats.time.php}
     *
     * @param $timeFormatString
     *
     * @return integer
     */
    public static function timeFormatToSeconds($timeFormatString)
    {
        $interval = new DateInterval($timeFormatString);

        return (int)$interval->toSeconds();
    }

    /**
     * Returns true if interval string is a valid interval.
     *
     * @param $intervalString
     *
     * @return boolean
     */
    public static function isValidIntervalString($intervalString)
    {
        $interval = DateInterval::createFromDateString($intervalString);

        if ($interval->s != 0 || $interval->i != 0 || $interval->h != 0 || $interval->d != 0 || $interval->m != 0 || $interval->y != 0) {
            return true;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns translation pairs for [[translateDate()]].
     *
     * @param string $language The target language
     *
     * @return array The translation pairs
     */
    private static function _getDateTranslations($language)
    {
        if (!isset(static::$_translationPairs[$language])) {
            if (strncmp(Craft::$app->language, 'en', 2) === 0) {
                $sourceLocale = Craft::$app->getLocale();
            } else {
                $sourceLocale = Craft::$app->getI18n()->getLocaleById('en-US');
            }

            $targetLocale = Craft::$app->getI18n()->getLocaleById($language);

            $amName = $targetLocale->getAMName();
            $pmName = $targetLocale->getPMName();

            static::$_translationPairs[$language] = array_merge(
                array_combine($sourceLocale->getMonthNames(Locale::LENGTH_FULL), $targetLocale->getMonthNames(Locale::LENGTH_FULL)),
                array_combine($sourceLocale->getWeekDayNames(Locale::LENGTH_FULL), $targetLocale->getWeekDayNames(Locale::LENGTH_FULL)),
                array_combine($sourceLocale->getMonthNames(Locale::LENGTH_MEDIUM), $targetLocale->getMonthNames(Locale::LENGTH_MEDIUM)),
                array_combine($sourceLocale->getWeekDayNames(Locale::LENGTH_MEDIUM), $targetLocale->getWeekDayNames(Locale::LENGTH_MEDIUM)),
                [
                    'AM' => StringHelper::toUpperCase($amName),
                    'PM' => StringHelper::toUpperCase($pmName),
                    'am' => StringHelper::toLowerCase($amName),
                    'pm' => StringHelper::toLowerCase($pmName)
                ]
            );
        }

        return static::$_translationPairs[$language];
    }
}
