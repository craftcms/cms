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
    public array $dateTimeFormats;

    /**
     * @var array|null The localized "stand alone" month names.
     */
    public ?array $standAloneMonthNames = null;

    /**
     * @var array|null The localized month names.
     */
    public ?array $monthNames = null;

    /**
     * @var array|null The localized "stand alone" day of the week names.
     */
    public ?array $standAloneWeekDayNames = null;

    /**
     * @var array|null The localized day of the week names.
     */
    public ?array $weekDayNames = null;

    /**
     * @var string|null The localized AM name.
     */
    public ?string $amName = null;

    /**
     * @var string|null The localized PM name.
     */
    public ?string $pmName = null;

    /**
     * @var array|null The locale's currency symbols.
     */
    public ?array $currencySymbols = null;

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
            return $this->_formatDateTimeValueWithPhpFormat($value, substr($format, 4), 'date');
        }

        return parent::asDate($value, $format);
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
            return $this->_formatDateTimeValueWithPhpFormat($value, substr($format, 4), 'time');
        }

        return parent::asTime($value, $format);
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
            return $this->_formatDateTimeValueWithPhpFormat($value, substr($format, 4), 'datetime');
        }

        return parent::asDatetime($value, $format);
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
     * - a string that can be [parsed to create a DateTime object](https://php.net/manual/en/datetime.formats.php).
     *   The timestamp is assumed to be in [[defaultTimeZone]] unless a time zone is explicitly given.
     * - a PHP [DateTime](https://php.net/manual/en/class.datetime.php) object
     * @param string|null $format The format used to convert the value into a date string.
     * If null, [[dateFormat]] will be used.
     * This can be "short", "medium", "long", or "full", which represents a preset format of different lengths.
     * It can also be a custom format as specified in the [ICU manual](http://userguide.icu-project.org/formatparse/datetime).
     * Alternatively this can be a string prefixed with `php:` representing a format that can be recognized by the
     * PHP [date()](https://php.net/manual/en/function.date.php)-function.
     * @param bool $withPreposition Whether a preposition should be included in the returned string
     * (e.g. “**at** 12:00 PM” or “**on** Wednesday”).
     * @return string the formatted result.
     * @throws InvalidArgumentException if the input value can not be evaluated as a date value.
     * @throws InvalidConfigException if the date format is invalid.
     * @see datetimeFormat
     */
    public function asTimestamp($value, ?string $format = null, bool $withPreposition = false): string
    {
        /** @var DateTime $timestamp */
        /** @var bool $hasTimeInfo */
        /** @var bool $hasDateInfo */
        [$timestamp, $hasTimeInfo, $hasDateInfo] = $this->normalizeDatetimeValue($value, true);

        // If it's today or missing date info, just return the local time.
        if (!$hasDateInfo || DateTimeHelper::isToday($timestamp)) {
            if ($hasTimeInfo) {
                $time = $this->asTime($timestamp, $format);
                return $withPreposition ? Craft::t('app', 'at {time}', ['time' => $time]) : $time;
            }
            return $withPreposition ? Craft::t('app', 'today') : Craft::t('app', 'Today');
        }

        // If it was yesterday, display 'Yesterday'
        if (DateTimeHelper::isYesterday($timestamp)) {
            return $withPreposition ? Craft::t('app', 'yesterday') : Craft::t('app', 'Yesterday');
        }

        // If it were up to 7 days ago, display the weekday name.
        if (DateTimeHelper::isWithinLast($timestamp, '7 days')) {
            $day = (int)$timestamp->format('w');
            $dayName = Craft::$app->getLocale()->getWeekDayName($day);
            return $withPreposition ? Craft::t('app', 'on {day}', ['day' => $dayName]) : $dayName;
        }

        // Otherwise, just return the local date.
        $date = $this->asDate($timestamp, $format);
        return $withPreposition ? Craft::t('app', 'on {date}', ['date' => $date]) : $date;
    }

    /**
     * @inheritdoc
     */
    public function asPercent($value, $decimals = null, $options = [], $textOptions = []): string
    {
        if (empty($value)) {
            $value = 0;
        } elseif ($decimals === null && is_numeric($value)) {
            $decimals = strpos(strrev((string)($value * 100)), '.') ?: 0;
        }

        return parent::asPercent($value, $decimals, $options, $textOptions);
    }

    /**
     * Formats the value as a currency number.
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
    public function asCurrency($value, $currency = null, $options = [], $textOptions = [], bool $stripZeros = false): string
    {
        $omitDecimals = ($stripZeros && (int)$value == $value);

        if ($omitDecimals) {
            $options[NumberFormatter::MAX_FRACTION_DIGITS] = 0;
            $options[NumberFormatter::MIN_FRACTION_DIGITS] = 0;
        }

        return parent::asCurrency($value, $currency, $options, $textOptions);
    }

    /**
     * @inheritdoc
     * @param string|DateTime|null $value
     * @return string
     */
    public function asText($value): string
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
    public function asShortSize($value, $decimals = null, $options = [], $textOptions = []): string
    {
        return strtoupper(parent::asShortSize($value, $decimals, $options, $textOptions));
    }

    /**
     * Returns whether the given number will be misrepresented when formatted.
     *
     * @param mixed $value the value to be formatted.
     * @return bool
     * @see isNormalizedValueMispresented()
     * @since 3.7.24
     */
    public function willBeMisrepresented(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return $this->isNormalizedValueMispresented($value, $this->normalizeNumericValue($value));
    }

    /**
     * Formats a value as a date, using a PHP date format.
     *
     * @param int|string|DateTime $value
     * @param string $format
     * @param string $type 'date', 'time', or 'datetime'.
     * @return string
     */
    private function _formatDateTimeValueWithPhpFormat(mixed $value, string $format, string $type): string
    {
        // special cases for PHP format characters not supported by ICU
        /** @var string[] $split */
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
                        // Make sure we are formatting the date with the right timezone consistently
                        if (!isset($timestamp)) {
                            [$timestamp, $hasTimeInfo, $hasDateInfo] = $this->normalizeDatetimeValue($value, true);
                            if ($type === 'date' && !$hasTimeInfo || $type === 'time' && !$hasDateInfo) {
                                $timeZone = $this->defaultTimeZone;
                            } else {
                                $timeZone = $this->timeZone;
                            }
                            if ($timeZone) {
                                $timestamp->setTimezone(new DateTimeZone($timeZone));
                            }
                        }

                        $formatted .= $timestamp->format($seg);
                }
            }
        }

        return $formatted;
    }
}
