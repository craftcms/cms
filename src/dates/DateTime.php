<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\dates;

use Craft;
use craft\app\helpers\DateTimeHelper;
use craft\app\i18n\Locale;

/**
 * Class DateTime
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DateTime extends \DateTime
{
    // Public Methods
    // =========================================================================

    /**
     * @return string
     * @deprecated in 3.0. Use `format('Y-m-d')` instead.
     */
    public function __toString()
    {
        Craft::$app->getDeprecator()->log('DateTime::__toString()', 'Converting a DateTime object to a string has been deprecated. Use format(\'Y-m-d\') instead.');
        return $this->format('Y-m-d');
    }

    /**
     * Creates a new [[DateTime]] object (rather than \DateTime)
     *
     * @param string $format
     * @param string $time
     * @param mixed  $timezone The timezone the string is set in (defaults to UTC).
     *
     * @return DateTime|false
     */
    public static function createFromFormat($format, $time, $timezone = null)
    {
        if ($timezone !== null) {
            $dateTime = parent::createFromFormat($format, $time, $timezone);
        } else {
            $dateTime = parent::createFromFormat($format, $time);
        }

        if ($dateTime) {
            $timeStamp = $dateTime->getTimestamp();

            if (DateTimeHelper::isValidTimeStamp($timeStamp)) {
                return new DateTime('@'.$dateTime->getTimestamp());
            }
        }

        return false;
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::ATOM)` instead.
     */
    public function atom()
    {
        Craft::$app->getDeprecator()->log('DateTime::atom()', 'DateTime::atom() has been deprecated. Use format(DateTime::ATOM) instead.');
        return $this->format(static::ATOM);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::COOKIE)` instead.
     */
    public function cookie()
    {
        Craft::$app->getDeprecator()->log('DateTime::cookie()', 'DateTime::cookie() has been deprecated. Use format(DateTime::COOKIE) instead.');
        return $this->format(static::COOKIE);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::ISO8601)` instead.
     */
    public function iso8601()
    {
        Craft::$app->getDeprecator()->log('DateTime::iso8601()', 'DateTime::iso8601() has been deprecated. Use format(DateTime::ISO8601) instead.');
        return $this->format(static::ISO8601);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RFC822)` instead.
     */
    public function rfc822()
    {
        Craft::$app->getDeprecator()->log('DateTime::rfc822()', 'DateTime::rfc822() has been deprecated. Use format(DateTime::RFC822) instead.');
        return $this->format(static::RFC822);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RFC850)` instead.
     */
    public function rfc850()
    {
        Craft::$app->getDeprecator()->log('DateTime::rfc850()', 'DateTime::rfc850() has been deprecated. Use format(DateTime::RFC850) instead.');
        return $this->format(static::RFC850);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RFC1036)` instead.
     */
    public function rfc1036()
    {
        Craft::$app->getDeprecator()->log('DateTime::rfc1036()', 'DateTime::rfc1036() has been deprecated. Use format(DateTime::RFC1036) instead.');
        return $this->format(static::RFC1036);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RFC1123)` instead.
     */
    public function rfc1123()
    {
        Craft::$app->getDeprecator()->log('DateTime::rfc1123()', 'DateTime::rfc1123() has been deprecated. Use format(DateTime::RFC1123) instead.');
        return $this->format(static::RFC1123);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RFC2822)` instead.
     */
    public function rfc2822()
    {
        Craft::$app->getDeprecator()->log('DateTime::rfc2822()', 'DateTime::rfc2822() has been deprecated. Use format(DateTime::RFC2822) instead.');
        return $this->format(static::RFC2822);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RFC3339)` instead.
     */
    public function rfc3339()
    {
        Craft::$app->getDeprecator()->log('DateTime::rfc3339()', 'DateTime::rfc3339() has been deprecated. Use format(DateTime::RFC3339) instead.');
        return $this->format(static::RFC3339);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::RSS)` instead.
     */
    public function rss()
    {
        Craft::$app->getDeprecator()->log('DateTime::rss()', 'DateTime::rss() has been deprecated. Use format(DateTime::RSS) instead.');
        return $this->format(static::RSS);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format(DateTime::W3C)` instead.
     */
    public function w3c()
    {
        Craft::$app->getDeprecator()->log('DateTime::w3c()', 'DateTime::w3c() has been deprecated. Use format(DateTime::W3C) instead.');
        return $this->format(static::W3C);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format('Y-m-d')` instead.
     */
    public function w3cDate()
    {
        Craft::$app->getDeprecator()->log('DateTime::w3cDate()', 'DateTime::w3cDate() has been deprecated. Use format(\'Y-m-d\') instead.');
        return $this->format('Y-m-d');
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format('Y-m-d H:i:s')` instead.
     */
    public function mySqlDateTime()
    {
        Craft::$app->getDeprecator()->log('DateTime::mySqlDateTime()', 'DateTime::mySqlDateTime() has been deprecated. Use format(\'Y-m-d H:i:s\') instead.');
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `Craft::$app->formatter->asDate($date, 'short')` instead.
     */
    public function localeDate()
    {
        Craft::$app->getDeprecator()->log('DateTime::localeDate()', 'DateTime::localeDate() has been deprecated. Use Craft::$app->formatter->asDate($date, \'short\') instead.');
        return Craft::$app->getFormatter()->asDate($this, Locale::LENGTH_SHORT);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `Craft::$app->formatter->asTime($date, 'short')` instead.
     */
    public function localeTime()
    {
        Craft::$app->getDeprecator()->log('DateTime::localeTime()', 'DateTime::localeTime() has been deprecated. Use Craft::$app->formatter->asTime($date, \'short\') instead.');
        return Craft::$app->getFormatter()->asTime($this, Locale::LENGTH_SHORT);
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format('Y')` instead.
     */
    public function year()
    {
        Craft::$app->getDeprecator()->log('DateTime::year()', 'DateTime::year() has been deprecated. Use format(\'Y\') instead.');
        return $this->format('Y');
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format('n')` instead.
     */
    public function month()
    {
        Craft::$app->getDeprecator()->log('DateTime::month()', 'DateTime::month() has been deprecated. Use format(\'n\') instead.');
        return $this->format('n');
    }

    /**
     * @return string
     * @deprecated in 3.0. Use `format('j')` instead.
     */
    public function day()
    {
        Craft::$app->getDeprecator()->log('DateTime::day()', 'DateTime::day() has been deprecated. Use format(\'j\') instead.');
        return $this->format('j');
    }

    /**
     * @param \DateTime $datetime2
     * @param boolean   $absolute
     *
     * @return DateInterval
     */
    public function diff($datetime2, $absolute = false)
    {
        $interval = parent::diff($datetime2, $absolute);

        // Convert it to a DateInterval in this namespace
        if ($interval instanceof \DateInterval) {
            $spec = 'P';

            if ($interval->y) {
                $spec .= $interval->y.'Y';
            }
            if ($interval->m) {
                $spec .= $interval->m.'M';
            }
            if ($interval->d) {
                $spec .= $interval->d.'D';
            }

            if ($interval->h || $interval->i || $interval->s) {
                $spec .= 'T';

                if ($interval->h) {
                    $spec .= $interval->h.'H';
                }
                if ($interval->i) {
                    $spec .= $interval->i.'M';
                }
                if ($interval->s) {
                    $spec .= $interval->s.'S';
                }
            }

            // If $spec is P at this point, the interval was less than a second. Accuracy be damned.
            if ($spec === 'P') {
                $spec = 'PT0S';
            }

            $newInterval = new DateInterval($spec);
            $newInterval->invert = $interval->invert;

            // Apparently 'days' is a read-only property. Oh well.
            //$newInterval->days = $interval->days;

            return $newInterval;
        }

        return $interval;
    }

    /**
     * Returns a nicely formatted date string.
     *
     * @return string
     * @deprecated in 3.0. Use `Craft::$app->formatter->asDatetime($date)` instead.
     */
    public function nice()
    {
        Craft::$app->getDeprecator()->log('DateTime::nice()', 'DateTime::nice() has been deprecated. Use Craft::$app->formatter->asDatetime($date) instead.');
        return Craft::$app->getFormatter()->asDatetime($this);
    }

    /**
     * Returns a UI-facing timestamp.
     *
     * - If the date/time is from today, only the time will be retuned in a localized format (e.g. “10:00 AM”).
     * - If the date/time is from yesterday, “Yesterday” will be returned.
     * - If the date/time is from the last 7 days, the name of the day will be returned (e.g. “Monday”).
     * - Otherwise, the date will be returned in a localized format (e.g. “12/2/2014”).
     *
     * @return string
     * @deprecated in 3.0. Use `Craft::$app->formatter->asTimestamp($date, 'short')` instead.
     */
    public function uiTimestamp()
    {
        Craft::$app->getDeprecator()->log('DateTime::uiTimestamp()', 'DateTime::uiTimestamp() has been deprecated. Use Craft::$app->formatter->asTimestamp($date, \'short\') instead.');
        return Craft::$app->getFormatter()->asTimestamp($this, Locale::LENGTH_SHORT);
    }
}
