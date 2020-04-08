<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;
use craft\helpers\DateTimeHelper;
use DateTime;
use DateTimeZone;
use NumberFormatter;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Formatter extends \yii\i18n\Formatter
{
    /**
     * @var array The locale’s date/time formats.
     */
    public $dateTimeFormats;

    /**
     * @var array|null The localized "stand alone" month names.
     */
    public $standAloneMonthNames;

    /**
     * @var array|null The localized month names.
     */
    public $monthNames;

    /**
     * @var array|null The localized "stand alone" day of the week names.
     */
    public $standAloneWeekDayNames;

    /**
     * @var array|null The localized day of the week names.
     */
    public $weekDayNames;

    /**
     * @var string|null The localized AM name.
     */
    public $amName;

    /**
     * @var string|null The localized PM name.
     */
    public $pmName;

    /**
     * @var array|null The locale's currency symbols.
     */
    public $currencySymbols;

    /**
     * @inheritdoc
     * @param int|string|DateTime $value
     * @param string|null $format
     * @return string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function asDate($value, $format = null): string
    {
        if ($format === null) {
            $format = $this->dateFormat;
        }

        if (isset($this->dateTimeFormats[$format]['date'])) {
            $format = $this->dateTimeFormats[$format]['date'];
        }

        if (strncmp($format, 'php:', 4) === 0) {
            $format = substr($format, 4);
            // special cases for PHP format characters not supported by ICU
            $split = preg_split('/(?<!\\\\)(S|w|t|L|B|u|I|Z|U|A|a)/', $format, -1, PREG_SPLIT_DELIM_CAPTURE);
            $formatted = '';
            foreach (array_filter($split) as $i => $seg) {
                if ($i % 2 === 0) {
                    $formatted .= $this->asDate($value, FormatConverter::convertDatePhpToIcu($seg));
                } else {
                    switch ($seg) {
                        case 'A':
                            $formatted .= mb_strtoupper($this->asDate($value, FormatConverter::convertDatePhpToIcu($seg)));
                            break;
                        case 'a':
                            $formatted .= mb_strtolower($this->asDate($value, FormatConverter::convertDatePhpToIcu($seg)));
                            break;
                        default:
                            $formatted .= $value->format($seg);
                    }
                }
            }
            return $formatted;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return parent::asDate($value, $format);
        }

        return $this->_formatDateTimeValue($value, $format, 'date');
    }

    /**
     * @inheritdoc
     * @param int|string|DateTime $value
     * @param string|null $format
     * @return string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function asTime($value, $format = null): string
    {
        if ($format === null) {
            $format = $this->timeFormat;
        }

        if (isset($this->dateTimeFormats[$format]['time'])) {
            $format = $this->dateTimeFormats[$format]['time'];
        }

        if (strncmp($format, 'php:', 4) === 0) {
            $format = FormatConverter::convertDatePhpToIcu(substr($format, 4));
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return parent::asTime($value, $format);
        }

        return $this->_formatDateTimeValue($value, $format, 'time');
    }

    /**
     * @inheritdoc
     * @param int|string|DateTime $value
     * @param string|null $format
     * @return string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function asDatetime($value, $format = null): string
    {
        if ($format === null) {
            $format = $this->datetimeFormat;
        }

        if (isset($this->dateTimeFormats[$format]['datetime'])) {
            $format = $this->dateTimeFormats[$format]['datetime'];
        }

        if (strncmp($format, 'php:', 4) === 0) {
            $format = FormatConverter::convertDatePhpToIcu(substr($format, 4));
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return parent::asDatetime($value, $format);
        }

        return $this->_formatDateTimeValue($value, $format, 'datetime');
    }

    /**
     * Formats the value as a human-readable timestamp.
     *
     * - If $value is from today, "Today" or the formatted time will be returned, depending on whether $value contains time information
     * - If $value is from yesterday, "Yesterday" will be returned
     * - If $value is within the past 7 days, the weekday will be returned
     *
     * @param int|string|DateTime $value The value to be formatted. The following
     * types of value are supported:
     * - an int representing a UNIX timestamp
     * - a string that can be [parsed to create a DateTime object](http://php.net/manual/en/datetime.formats.php).
     *   The timestamp is assumed to be in [[defaultTimeZone]] unless a time zone is explicitly given.
     * - a PHP [DateTime](http://php.net/manual/en/class.datetime.php) object
     * @param string|null $format The format used to convert the value into a date string.
     * If null, [[dateFormat]] will be used.
     * This can be "short", "medium", "long", or "full", which represents a preset format of different lengths.
     * It can also be a custom format as specified in the [ICU manual](http://userguide.icu-project.org/formatparse/datetime).
     * Alternatively this can be a string prefixed with `php:` representing a format that can be recognized by the
     * PHP [date()](http://php.net/manual/en/function.date.php)-function.
     * @return string the formatted result.
     * @throws InvalidArgumentException if the input value can not be evaluated as a date value.
     * @throws InvalidConfigException if the date format is invalid.
     * @see datetimeFormat
     */
    public function asTimestamp($value, string $format = null): string
    {
        /** @var DateTime $timestamp */
        /** @var bool $hasTimeInfo */
        /** @var bool $hasDateInfo */
        list($timestamp, $hasTimeInfo, $hasDateInfo) = $this->normalizeDatetimeValue($value, true);

        // If it's today or missing date info, just return the local time.
        if (!$hasDateInfo || DateTimeHelper::isToday($timestamp)) {
            return $hasTimeInfo ? $this->asTime($timestamp, $format) : Craft::t('app', 'Today');
        }

        // If it was yesterday, display 'Yesterday'
        if (DateTimeHelper::isYesterday($timestamp)) {
            return Craft::t('app', 'Yesterday');
        }

        // If it were up to 7 days ago, display the weekday name.
        if (DateTimeHelper::isWithinLast($timestamp, '7 days')) {
            $day = $timestamp->format('w');
            return Craft::$app->getI18n()->getLocaleById($this->locale)->getWeekDayName($day);
        }

        // Otherwise, just return the local date.
        return $this->asDate($timestamp, $format);
    }

    /**
     * Formats the value as a currency number.
     *
     * This function does not requires the [PHP intl extension](http://php.net/manual/en/book.intl.php) to be installed
     * to work but it is highly recommended to install it to get good formatting results.
     *
     * @param mixed $value the value to be formatted.
     * @param string|null $currency the 3-letter ISO 4217 currency code indicating the currency to use.
     * If null, [[currencyCode]] will be used.
     * @param array $options optional configuration for the number formatter. This parameter will be merged with [[numberFormatterOptions]].
     * @param array $textOptions optional configuration for the number formatter. This parameter will be merged with [[numberFormatterTextOptions]].
     * @param bool $stripZeros Whether the formatted currency should remove the fraction digits if $value has no minor value (e.g. cents).
     * @return string the formatted result.
     * @throws InvalidArgumentException if the input value is not numeric.
     * @throws InvalidConfigException if no currency is given and [[currencyCode]] is not defined.
     */
    public function asCurrency($value, $currency = null, $options = [], $textOptions = [], $stripZeros = false): string
    {
        $omitDecimals = ($stripZeros && (int)$value == $value);

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            if ($omitDecimals) {
                $options[NumberFormatter::MAX_FRACTION_DIGITS] = 0;
                $options[NumberFormatter::MIN_FRACTION_DIGITS] = 0;
            }

            return parent::asCurrency($value, $currency, $options, $textOptions);
        }

        // Code adapted from \yii\i18n\Formatter
        if ($value === null) {
            return $this->nullDisplay;
        }

        $value = $this->normalizeNumericValue($value);

        if ($currency === null) {
            if ($this->currencyCode === null) {
                throw new InvalidConfigException('The default currency code for the formatter is not defined.');
            }

            $currency = $this->currencyCode;
        }

        // Do we have a localized symbol for this currency?
        if (isset($this->currencySymbols[$currency])) {
            $currency = $this->currencySymbols[$currency];
        }

        $decimals = $omitDecimals ? 0 : 2;

        return $currency . $this->asDecimal($value, $decimals, $options, $textOptions);
    }

    /**
     * @inheritdoc
     */
    public function asText($value)
    {
        if ($value instanceof DateTime) {
            return $this->asDatetime($value);
        }

        return parent::asText($value);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function asShortSize($value, $decimals = null, $options = [], $textOptions = [])
    {
        return strtoupper(parent::asShortSize($value, $decimals, $options, $textOptions));
    }

    /**
     * Formats a given date/time.
     *
     * Code mostly copied from [[parent::formatDateTimeValue()]], with the exception that translatable strings
     * in the date/time format will be returned in the correct locale.
     *
     * @param int|string|DateTime $value The value to be formatted. The following
     * types of value are supported:
     * - an int representing a UNIX timestamp
     * - a string that can be [parsed to create a DateTime object](http://php.net/manual/en/datetime.formats.php).
     *   The timestamp is assumed to be in [[defaultTimeZone]] unless a time zone is explicitly given.
     * - a PHP [DateTime](http://php.net/manual/en/class.datetime.php) object
     * @param string $format The format used to convert the value into a date string.
     * @param string $type 'date', 'time', or 'datetime'.
     * @return string the formatted result.
     * @throws InvalidConfigException if the date format is invalid.
     */
    private function _formatDateTimeValue($value, string $format, string $type): string
    {
        $timeZone = $this->timeZone;

        // Avoid time zone conversion for date-only values
        if ($type === 'date') {
            list($timestamp, $hasTimeInfo) = $this->normalizeDatetimeValue($value, true);

            if (!$hasTimeInfo) {
                $timeZone = $this->defaultTimeZone;
            }
        } else {
            $timestamp = $this->normalizeDatetimeValue($value);
        }

        if ($timestamp === null) {
            return $this->nullDisplay;
        }

        if ($timeZone != null) {
            $timestamp->setTimezone(new DateTimeZone($timeZone));
        }

        // Parse things that we can translate before passing it off to DateTime::format()
        $tr = [];

        if (preg_match_all('/(?<!\')\'.*?[^\']\'(?!\')/', $format, $matches)) {
            foreach ($matches[0] as $match) {
                $tr[$match] = $match;
            }
        }

        if ($this->standAloneMonthNames !== null || $this->monthNames !== null) {
            $month = $timestamp->format('n') - 1;

            if ($this->standAloneMonthNames !== null) {
                $tr['LLLLL'] = '\'' . $this->standAloneMonthNames['abbreviated'][$month] . '\'';
                $tr['LLLL'] = '\'' . $this->standAloneMonthNames['full'][$month] . '\'';
                $tr['LLL'] = '\'' . $this->standAloneMonthNames['medium'][$month] . '\'';
            }

            if ($this->monthNames !== null) {
                $tr['MMMMM'] = '\'' . $this->monthNames['abbreviated'][$month] . '\'';
                $tr['MMMM'] = '\'' . $this->monthNames['full'][$month] . '\'';
                $tr['MMM'] = '\'' . $this->monthNames['medium'][$month] . '\'';
            }
        }

        if ($this->standAloneWeekDayNames !== null || $this->weekDayNames !== null) {
            $day = $timestamp->format('w');

            if ($this->standAloneWeekDayNames !== null) {
                $tr['cccccc'] = '\'' . $this->standAloneWeekDayNames['short'][$day] . '\'';
                $tr['ccccc'] = '\'' . $this->standAloneWeekDayNames['abbreviated'][$day] . '\'';
                $tr['cccc'] = '\'' . $this->standAloneWeekDayNames['full'][$day] . '\'';
                $tr['ccc'] = '\'' . $this->standAloneWeekDayNames['medium'][$day] . '\'';
            }

            if ($this->weekDayNames !== null) {
                $tr['EEEEEE'] = '\'' . $this->weekDayNames['short'][$day] . '\'';
                $tr['EEEEE'] = '\'' . $this->weekDayNames['abbreviated'][$day] . '\'';
                $tr['EEEE'] = '\'' . $this->weekDayNames['full'][$day] . '\'';
                $tr['EEE'] = '\'' . $this->weekDayNames['medium'][$day] . '\'';
                $tr['EE'] = '\'' . $this->weekDayNames['medium'][$day] . '\'';
                $tr['E'] = '\'' . $this->weekDayNames['medium'][$day] . '\'';

                $tr['eeeeee'] = '\'' . $this->weekDayNames['short'][$day] . '\'';
                $tr['eeeee'] = '\'' . $this->weekDayNames['abbreviated'][$day] . '\'';
                $tr['eeee'] = '\'' . $this->weekDayNames['full'][$day] . '\'';
                $tr['eee'] = '\'' . $this->weekDayNames['medium'][$day] . '\'';
            }
        }

        $amPmName = $timestamp->format('a') . 'Name';

        if ($this->$amPmName !== null) {
            $tr['a'] = '\'' . $this->$amPmName . '\'';
        }

        if (!empty($tr)) {
            $format = strtr($format, $tr);
        }

        $format = FormatConverter::convertDateIcuToPhp($format, $type, $this->locale);

        return $timestamp->format($format);
    }
}
