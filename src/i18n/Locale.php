<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;
use DateTime;
use IntlDateFormatter;
use NumberFormatter;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Locale extends BaseObject
{
    /**
     * @var int Positive prefix.
     */
    public const ATTR_POSITIVE_PREFIX = 0;

    /**
     * @var int Positive suffix.
     */
    public const ATTR_POSITIVE_SUFFIX = 1;

    /**
     * @var int Negative prefix.
     */
    public const ATTR_NEGATIVE_PREFIX = 2;

    /**
     * @var int Negative suffix.
     */
    public const ATTR_NEGATIVE_SUFFIX = 3;

    /**
     * @var int The character used to pad to the format width.
     */
    public const ATTR_PADDING_CHARACTER = 4;

    /**
     * @var int The ISO currency code.
     */
    public const ATTR_CURRENCY_CODE = 5;

    /**
     * @var int The default rule set. This is only available with rule-based
     * formatters.
     */
    public const ATTR_DEFAULT_RULESET = 6;

    /**
     * @var int The public rule sets. This is only available with rule-based
     * formatters. This is a read-only attribute. The public rulesets are
     * returned as a single string, with each ruleset name delimited by ';'
     * (semicolon).
     */
    public const ATTR_PUBLIC_RULESETS = 7;

    /**
     * @var int Decimal style
     */
    public const STYLE_DECIMAL = 1;

    /**
     * @var int Currency style
     */
    public const STYLE_CURRENCY = 2;

    /**
     * @var int Percent style
     */
    public const STYLE_PERCENT = 3;

    /**
     * @var int Scientific style
     */
    public const STYLE_SCIENTIFIC = 4;

    /**
     * @var int The decimal separator.
     */
    public const SYMBOL_DECIMAL_SEPARATOR = 0;

    /**
     * @var int The grouping separator.
     */
    public const SYMBOL_GROUPING_SEPARATOR = 1;

    /**
     * @var int The pattern separator.
     */
    public const SYMBOL_PATTERN_SEPARATOR = 2;

    /**
     * @var int The percent sign.
     */
    public const SYMBOL_PERCENT = 3;

    /**
     * @var int Zero.
     */
    public const SYMBOL_ZERO_DIGIT = 4;

    /**
     * @var int Character representing a digit in the pattern.
     */
    public const SYMBOL_DIGIT = 5;

    /**
     * @var int The minus sign.
     */
    public const SYMBOL_MINUS_SIGN = 6;

    /**
     * @var int The plus sign.
     */
    public const SYMBOL_PLUS_SIGN = 7;

    /**
     * @var int The currency symbol.
     */
    public const SYMBOL_CURRENCY = 8;

    /**
     * @var int The international currency symbol.
     */
    public const SYMBOL_INTL_CURRENCY = 9;

    /**
     * @var int The monetary separator.
     */
    public const SYMBOL_MONETARY_SEPARATOR = 10;

    /**
     * @var int The exponential symbol.
     */
    public const SYMBOL_EXPONENTIAL = 11;

    /**
     * @var int Per mill symbol.
     */
    public const SYMBOL_PERMILL = 12;

    /**
     * @var int Escape padding character.
     */
    public const SYMBOL_PAD_ESCAPE = 13;

    /**
     * @var int Infinity symbol.
     */
    public const SYMBOL_INFINITY = 14;

    /**
     * @var int Not-a-number symbol.
     */
    public const SYMBOL_NAN = 15;

    /**
     * @var int Significant digit symbol.
     */
    public const SYMBOL_SIGNIFICANT_DIGIT = 16;

    /**
     * @var int The monetary grouping separator.
     */
    public const SYMBOL_MONETARY_GROUPING_SEPARATOR = 17;

    /**
     * @var string The abbreviated date/time format.
     */
    public const LENGTH_ABBREVIATED = 'abbreviated';

    /**
     * @var string The short date/time format.
     */
    public const LENGTH_SHORT = 'short';

    /**
     * @var string The medium date/time format.
     */
    public const LENGTH_MEDIUM = 'medium';

    /**
     * @var string The long date/time format.
     */
    public const LENGTH_LONG = 'long';

    /**
     * @var string The full date/time format.
     */
    public const LENGTH_FULL = 'full';

    /**
     * @var string ICU format
     */
    public const FORMAT_ICU = 'icu';

    /**
     * @var string PHP format
     */
    public const FORMAT_PHP = 'php';

    /**
     * @var string jQuery UI format
     */
    public const FORMAT_JUI = 'jui';

    /**
     * @var string Human-readable format
     * @since 4.3.0
     */
    public const FORMAT_HUMAN = 'human';

    /**
     * @var array The languages that use RTL orientation.
     */
    private static array $_rtlLanguages = [
        'ar',
        'fa',
        'he',
        'ps',
        'ur',
    ];

    /**
     * @var string|null The locale ID.
     */
    public ?string $id = null;

    /**
     * @var Formatter|null The locale's formatter.
     */
    private ?Formatter $_formatter = null;

    /**
     * Constructor.
     *
     * @param string $id The locale ID.
     * @param array $config Name-value pairs that will be used to initialize the object properties.
     * @throws InvalidArgumentException If $id is an unsupported locale.
     */
    public function __construct(string $id, array $config = [])
    {
        if (str_contains($id, '_')) {
            $id = str_replace('_', '-', $id);
        }

        $this->id = $id;

        parent::__construct($config);
    }

    /**
     * Use the ID as the string representation of locales.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->id;
    }

    /**
     * Returns this locale’s language ID.
     *
     * @return string This locale’s language ID.
     */
    public function getLanguageID(): string
    {
        if (($pos = strpos($this->id, '-')) !== false) {
            return substr($this->id, 0, $pos);
        }

        return $this->id;
    }

    /**
     * Returns this locale’s script ID.
     *
     * A script ID consists of only the last four characters after a dash in the locale ID.
     *
     * @return string|null The locale’s script ID, if it has one.
     */
    public function getScriptID(): ?string
    {
        // Find sub tags
        if (str_contains($this->id, '-')) {
            $subTag = explode('-', $this->id);

            // Script sub tags can be distinguished from territory sub tags by length
            if (strlen($subTag[1]) === 4) {
                return $subTag[1];
            }
        }

        return null;
    }

    /**
     * Returns this locale’s territory ID.
     *
     * A territory ID consists of only the last two to three letter or digits after a dash in the locale ID.
     *
     * @return string|null The locale’s territory ID, if it has one.
     */
    public function getTerritoryID(): ?string
    {
        // Find sub tags
        if (str_contains($this->id, '-')) {
            $subTag = explode('-', $this->id);

            // Territory sub tags can be distinguished from script sub tags by length
            if (isset($subTag[2]) && strlen($subTag[2]) < 4) {
                return $subTag[2];
            }

            if (strlen($subTag[1]) < 4) {
                return $subTag[1];
            }
        }

        return null;
    }

    /**
     * Returns the locale name in a given language.
     *
     * @param string|null $inLocale
     * @return string
     */
    public function getDisplayName(?string $inLocale = null): string
    {
        // If no target locale is specified, default to this locale
        if ($inLocale === null) {
            $inLocale = $this->id;
        }

        return \Locale::getDisplayName($this->id, $inLocale);
    }

    /**
     * Returns the language’s orientation (ltr or rtl).
     *
     * @return string The language’s orientation.
     */
    public function getOrientation(): string
    {
        if (in_array($this->getLanguageID(), self::$_rtlLanguages, true)) {
            return 'rtl';
        }

        return 'ltr';
    }

    /**
     * Returns a [[Formatter]] for this locale.
     *
     * @return Formatter A formatter for this locale.
     */
    public function getFormatter(): Formatter
    {
        if (!isset($this->_formatter)) {
            $config = [
                'class' => Formatter::class,
                'locale' => $this->id,
                'sizeFormatBase' => 1000,
                'dateTimeFormats' => [
                    self::LENGTH_SHORT => [
                        'date' => $this->getDateFormat(self::LENGTH_SHORT),
                        'time' => $this->getTimeFormat(self::LENGTH_SHORT),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_SHORT),
                    ],
                    self::LENGTH_MEDIUM => [
                        'date' => $this->getDateFormat(self::LENGTH_MEDIUM),
                        'time' => $this->getTimeFormat(self::LENGTH_MEDIUM),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_MEDIUM),
                    ],
                    self::LENGTH_LONG => [
                        'date' => $this->getDateFormat(self::LENGTH_LONG),
                        'time' => $this->getTimeFormat(self::LENGTH_LONG),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_LONG),
                    ],
                    self::LENGTH_FULL => [
                        'date' => $this->getDateFormat(self::LENGTH_FULL),
                        'time' => $this->getTimeFormat(self::LENGTH_FULL),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_FULL),
                    ],
                ],
            ];

            $this->_formatter = Craft::createObject($config);
        }

        return $this->_formatter;
    }

    // Date/Time Formatting
    // -------------------------------------------------------------------------

    /**
     * Returns the localized ICU date format.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The localized ICU date format.
     */
    public function getDateFormat(?string $length = null, string $format = self::FORMAT_ICU): string
    {
        return $this->_getDateTimeFormat($length, true, false, $format);
    }

    /**
     * Returns the localized ICU time format.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The localized ICU time format.
     */
    public function getTimeFormat(?string $length = null, string $format = self::FORMAT_ICU): string
    {
        return $this->_getDateTimeFormat($length, false, true, $format);
    }

    /**
     * Returns the localized ICU date + time format.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The localized ICU date + time format.
     */
    public function getDateTimeFormat(?string $length = null, string $format = self::FORMAT_ICU): string
    {
        return $this->_getDateTimeFormat($length, true, true, $format);
    }

    /**
     * Returns a localized month name.
     *
     * @param int $month The month to return (1-12).
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param bool $standAlone Whether to return the "stand alone" month name.
     * @return string The localized month name.
     */
    public function getMonthName(int $month, ?string $length = null, bool $standAlone = true): string
    {
        if ($length === null) {
            $length = self::LENGTH_FULL;
        }

        $formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

        switch ($length) {
            case self::LENGTH_ABBREVIATED:
                $formatter->setPattern($standAlone ? 'LLLLL' : 'MMMMM');
                break; // S
            case self::LENGTH_SHORT:
            case self::LENGTH_MEDIUM:
                $formatter->setPattern($standAlone ? 'LLL' : 'MMM');
                break; // Sep
            default:
                $formatter->setPattern($standAlone ? 'LLLL' : 'MMMM');
                break; // September
        }

        return $formatter->format(new DateTime('1970-' . sprintf('%02d', $month) . '-01'));
    }

    /**
     * Returns all of the localized month names.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param bool $standAlone Whether to return the "stand alone" month names.
     * @return array The localized month names.
     */
    public function getMonthNames(?string $length = null, bool $standAlone = true): array
    {
        $monthNames = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthNames[] = $this->getMonthName($month, $length, $standAlone);
        }

        return $monthNames;
    }

    /**
     * Returns a localized day of the week name.
     *
     * @param int $day The day of the week to return (0-6), where 0 stands for Sunday.
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::SHORT, ::MEDIUM, ::FULL
     * @param bool $standAlone Whether to return the "stand alone" day of the week name.
     * @return string The localized day of the week name.
     */
    public function getWeekDayName(int $day, ?string $length = null, bool $standAlone = true): string
    {
        if ($length === null) {
            $length = self::LENGTH_FULL;
        }

        $formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

        switch ($length) {
            case self::LENGTH_ABBREVIATED:
                // T
                $formatter->setPattern($standAlone ? 'ccccc' : 'eeeee');
                break;
            case self::LENGTH_SHORT:
                // Tu
                $formatter->setPattern($standAlone ? 'cccccc' : 'eeeeee');
                break;
            case self::LENGTH_MEDIUM:
                // Tue
                $formatter->setPattern($standAlone ? 'ccc' : 'eee');
                break;
            default:
                // Tuesday
                $formatter->setPattern($standAlone ? 'cccc' : 'eeee');
                break;
        }

        // 1970-01-04 => Sunday (0 + 4)
        // 1970-01-05 => Monday (1 + 4)
        // 1970-01-06 => Tuesday (2 + 4)
        // 1970-01-07 => Wednesday (3 + 4)
        // 1970-01-08 => Thursday (4 + 4)
        // 1970-01-09 => Friday (5 + 4)
        // 1970-01-10 => Saturday (6 + 4)
        return $formatter->format(new DateTime('1970-01-' . sprintf('%02d', $day + 4)));
    }

    /**
     * Returns all of the localized day of the week names.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param bool $standAlone Whether to return the "stand alone" day of the week names.
     * @return array The localized day of the week names.
     */
    public function getWeekDayNames(?string $length = null, bool $standAlone = true): array
    {
        $weekDayNames = [];

        for ($day = 0; $day <= 6; $day++) {
            $weekDayNames[] = $this->getWeekDayName($day, $length, $standAlone);
        }

        return $weekDayNames;
    }

    /**
     * Returns the "AM" name for this locale.
     *
     * @return string The "AM" name.
     */
    public function getAMName(): string
    {
        return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
    }

    /**
     * Returns the "PM" name for this locale.
     *
     * @return string The "PM" name.
     */
    public function getPMName(): string
    {
        return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
    }

    // Text Attributes and Symbols
    // -------------------------------------------------------------------------

    /**
     * Returns a text attribute used by this locale.
     *
     * @param int $attribute The attribute to return. Values: Locale::
     * @return string|null The attribute.
     */
    public function getTextAttribute(int $attribute): ?string
    {
        $formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
        return $formatter->getTextAttribute($attribute);
    }

    /**
     * Returns a number pattern used by this locale.
     *
     * @param int $style The pattern style to return.
     * Accepted values: Locale::STYLE_DECIMAL, ::STYLE_CURRENCY, ::STYLE_PERCENT, ::STYLE_SCIENTIFIC
     * @return string|null The pattern
     */
    public function getNumberPattern(int $style): ?string
    {
        $formatter = new NumberFormatter($this->id, $style);
        return $formatter->getPattern();
    }

    /**
     * Returns a number symbol used by this locale.
     *
     * @param int $symbol The symbol to return. Accepted values: Locale::SYMBOL_DECIMAL_SEPARATOR,
     * ::SYMBOL_GROUPING_SEPARATOR, ::SYMBOL_PATTERN_SEPARATOR, ::SYMBOL_PERCENT, ::SYMBOL_ZERO_DIGIT,
     * ::SYMBOL_DIGIT, ::SYMBOL_MINUS_SIGN, ::SYMBOL_PLUS_SIGN, ::SYMBOL_CURRENCY, ::SYMBOL_INTL_CURRENCY,
     * ::SYMBOL_MONETARY_SEPARATOR, ::SYMBOL_EXPONENTIAL, ::SYMBOL_PERMILL, ::SYMBOL_PAD_ESCAPE,
     * ::SYMBOL_INFINITY, ::SYMBOL_NAN, ::SYMBOL_SIGNIFICANT_DIGIT, ::SYMBOL_MONETARY_GROUPING_SEPARATOR
     * @return string|null The symbol.
     */
    public function getNumberSymbol(int $symbol): ?string
    {
        $formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
        return $formatter->getSymbol($symbol);
    }

    /**
     * Returns this locale’s symbol for a given currency.
     *
     * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
     * @return string The currency symbol.
     */
    public function getCurrencySymbol(string $currency): string
    {
        // hat tip: https://stackoverflow.com/a/30026774
        $formatter = new NumberFormatter("$this->id@currency=$currency", NumberFormatter::CURRENCY);
        return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Returns a localized date/time format.
     *
     * @param string $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param bool $withDate Whether the date should be included in the format.
     * @param bool $withTime Whether the time should be included in the format.
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI, ::FORMAT_HUMAN
     * @return string The date/time format
     */
    private function _getDateTimeFormat(string $length, bool $withDate, bool $withTime, string $format): string
    {
        $icuFormat = $this->_getDateTimeIcuFormat($length, $withDate, $withTime);

        if ($format !== self::FORMAT_ICU) {
            $type = ($withDate ? 'date' : '') . ($withTime ? 'time' : '');

            switch ($format) {
                case self::FORMAT_PHP:
                    return FormatConverter::convertDateIcuToPhp($icuFormat, $type, $this->id);
                case self::FORMAT_JUI:
                    return FormatConverter::convertDateIcuToJui($icuFormat, $type, $this->id);
                case self::FORMAT_HUMAN:
                    $php = FormatConverter::convertDateIcuToPhp($icuFormat, $type, $this->id);
                    return FormatConverter::convertDatePhpToHuman($php);
            }
        }

        return $icuFormat;
    }

    /**
     * Returns a localized ICU date/time format.
     *
     * @param string $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param bool $withDate Whether the date should be included in the format.
     * @param bool $withTime Whether the time should be included in the format.
     * @return string The ICU date/time format
     * @throws Exception if $length is invalid
     */
    private function _getDateTimeIcuFormat(string $length, bool $withDate, bool $withTime): string
    {
        // Convert length to IntlDateFormatter constants
        $length = match ($length) {
            self::LENGTH_FULL => IntlDateFormatter::FULL,
            self::LENGTH_LONG => IntlDateFormatter::LONG,
            self::LENGTH_MEDIUM => IntlDateFormatter::MEDIUM,
            self::LENGTH_SHORT => IntlDateFormatter::SHORT,
            default => throw new Exception('Invalid date/time format length: ' . $length),
        };

        $dateType = ($withDate ? $length : IntlDateFormatter::NONE);
        $timeType = ($withTime ? $length : IntlDateFormatter::NONE);
        $formatter = new IntlDateFormatter($this->id, $dateType, $timeType);
        $pattern = $formatter->getPattern();

        // Use 4-digit years
        return strtr($pattern, [
            'yyyy' => 'yyyy',
            'yy' => 'yyyy',
        ]);
    }
}
