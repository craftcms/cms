<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use DateTime;
use Illuminate\Support\Facades\Date;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Translation\Formatter} instead.
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

    public $nullDisplay = '';

    private function getFormatter(): \CraftCms\Cms\Translation\Formatter
    {
        $formatter = new \CraftCms\Cms\Translation\Formatter();
        $formatter->locale = $this->locale;
        $formatter->dateTimeFormats = $this->dateTimeFormats;
        $formatter->sizeFormatBase = $this->sizeFormatBase;

        return $formatter;
    }

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
        return $this->getFormatter()->asDate($value, $format);
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
        return $this->getFormatter()->asTime($value, $format);
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
        return $this->getFormatter()->asDateTime($value, $format);
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
        return $this->getFormatter()->asTimestamp($value, $format, $withPreposition);
    }

    /**
     * @inheritdoc
     */
    public function asPercent($value, $decimals = null, $options = [], $textOptions = []): string
    {
        return $this->getFormatter()->asPercent($value, $decimals);
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
        return $this->getFormatter()->asCurrency($value, $currency, $stripZeros);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function asShortSize($value, $decimals = null, $options = [], $textOptions = []): string
    {
        return $this->getFormatter()->asShortSize($value, $decimals);
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
}
