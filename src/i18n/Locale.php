<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;
use craft\helpers\Localization;
use DateTime;
use IntlDateFormatter;
use NumberFormatter;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\FormatConverter;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends BaseObject
{
    // Constants
    // =========================================================================

    /**
     * @var int Positive prefix.
     */
    const ATTR_POSITIVE_PREFIX = 0;

    /**
     * @var int Positive suffix.
     */
    const ATTR_POSITIVE_SUFFIX = 1;

    /**
     * @var int Negative prefix.
     */
    const ATTR_NEGATIVE_PREFIX = 2;

    /**
     * @var int Negative suffix.
     */
    const ATTR_NEGATIVE_SUFFIX = 3;

    /**
     * @var int The character used to pad to the format width.
     */
    const ATTR_PADDING_CHARACTER = 4;

    /**
     * @var int The ISO currency code.
     */
    const ATTR_CURRENCY_CODE = 5;

    /**
     * @var int The default rule set. This is only available with rule-based
     * formatters.
     */
    const ATTR_DEFAULT_RULESET = 6;

    /**
     * @var int The public rule sets. This is only available with rule-based
     * formatters. This is a read-only attribute. The public rulesets are
     * returned as a single string, with each ruleset name delimited by ';'
     * (semicolon).
     */
    const ATTR_PUBLIC_RULESETS = 7;

    /**
     * @var int Decimal style
     */
    const STYLE_DECIMAL = 1;

    /**
     * @var int Currency style
     */
    const STYLE_CURRENCY = 2;

    /**
     * @var int Percent style
     */
    const STYLE_PERCENT = 3;

    /**
     * @var int Scientific style
     */
    const STYLE_SCIENTIFIC = 4;

    /**
     * @var int The decimal separator.
     */
    const SYMBOL_DECIMAL_SEPARATOR = 0;

    /**
     * @var int The grouping separator.
     */
    const SYMBOL_GROUPING_SEPARATOR = 1;

    /**
     * @var int The pattern separator.
     */
    const SYMBOL_PATTERN_SEPARATOR = 2;

    /**
     * @var int The percent sign.
     */
    const SYMBOL_PERCENT = 3;

    /**
     * @var int Zero.
     */
    const SYMBOL_ZERO_DIGIT = 4;

    /**
     * @var int Character representing a digit in the pattern.
     */
    const SYMBOL_DIGIT = 5;

    /**
     * @var int The minus sign.
     */
    const SYMBOL_MINUS_SIGN = 6;

    /**
     * @var int The plus sign.
     */
    const SYMBOL_PLUS_SIGN = 7;

    /**
     * @var int The currency symbol.
     */
    const SYMBOL_CURRENCY = 8;

    /**
     * @var int The international currency symbol.
     */
    const SYMBOL_INTL_CURRENCY = 9;

    /**
     * @var int The monetary separator.
     */
    const SYMBOL_MONETARY_SEPARATOR = 10;

    /**
     * @var int The exponential symbol.
     */
    const SYMBOL_EXPONENTIAL = 11;

    /**
     * @var int Per mill symbol.
     */
    const SYMBOL_PERMILL = 12;

    /**
     * @var int Escape padding character.
     */
    const SYMBOL_PAD_ESCAPE = 13;

    /**
     * @var int Infinity symbol.
     */
    const SYMBOL_INFINITY = 14;

    /**
     * @var int Not-a-number symbol.
     */
    const SYMBOL_NAN = 15;

    /**
     * @var int Significant digit symbol.
     */
    const SYMBOL_SIGNIFICANT_DIGIT = 16;

    /**
     * @var int The monetary grouping separator.
     */
    const SYMBOL_MONETARY_GROUPING_SEPARATOR = 17;

    /**
     * @var string The abbreviated date/time format.
     */
    const LENGTH_ABBREVIATED = 'abbreviated';

    /**
     * @var string The short date/time format.
     */
    const LENGTH_SHORT = 'short';

    /**
     * @var string The medium date/time format.
     */
    const LENGTH_MEDIUM = 'medium';

    /**
     * @var string The long date/time format.
     */
    const LENGTH_LONG = 'long';

    /**
     * @var string The full date/time format.
     */
    const LENGTH_FULL = 'full';

    /**
     * @var string ICU format
     */
    const FORMAT_ICU = 'icu';

    /**
     * @var string PHP format
     */
    const FORMAT_PHP = 'php';

    /**
     * @var string jQuery UI format
     */
    const FORMAT_JUI = 'jui';

    // Properties
    // =========================================================================

    /**
     * @var array The languages that use RTL orientation.
     */
    private static $_rtlLanguages = ['ar', 'he', 'ur'];

    /**
     * @var string|null The locale ID.
     */
    public $id;

    /**
     * @var array|null The configured locale data, used if the [PHP intl extension](http://php.net/manual/en/book.intl.php) isn’t loaded.
     */
    private $_data;

    /**
     * @var Formatter|null The locale's formatter.
     */
    private $_formatter;

    // Public Methods
    // =========================================================================

    /**
     * Constructor.
     *
     * @param string $id The locale ID.
     * @param array $config Name-value pairs that will be used to initialize the object properties.
     * @throws InvalidArgumentException If $id is an unsupported locale.
     */
    public function __construct(string $id, array $config = [])
    {
        if (strpos($id, '_') !== false) {
            $id = str_replace('_', '-', $id);
        }

        $this->id = $id;

        if (!Craft::$app->getI18n()->getIsIntlLoaded()) {
            $this->_data = Localization::localeData($this->id);

            if ($this->_data === null && ($languageId = $this->getLanguageID()) !== $this->id) {
                $this->_data = Localization::localeData($languageId);
            }

            if ($this->_data === null) {
                $this->_data = Localization::localeData('en-US');
            }
        }

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
     * A script ID consists of only the last four characters after a dash in the locale ID.
     *
     * @return string|null The locale’s script ID, if it has one.
     */
    public function getScriptID()
    {
        // Find sub tags
        if (strpos($this->id, '-') !== false) {
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
     * A territory ID consists of only the last two to three letter or digits after a dash in the locale ID.
     *
     * @return string|null The locale’s territory ID, if it has one.
     */
    public function getTerritoryID()
    {
        // Find sub tags
        if (strpos($this->id, '-') !== false) {
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
    public function getDisplayName(string $inLocale = null): string
    {
        // If no target locale is specified, default to this locale
        if ($inLocale === null) {
            $inLocale = $this->id;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return \Locale::getDisplayName($this->id, $inLocale);
        }

        if ($inLocale === $this->id) {
            $locale = $this;
        } else {
            try {
                $locale = new Locale($inLocale);
            } catch (InvalidConfigException $e) {
                $locale = $this;
            }
        }

        if (isset($locale->_data['localeDisplayNames'][$this->id])) {
            return $locale->_data['localeDisplayNames'][$this->id];
        }

        // Try just the language
        $languageId = $this->getLanguageID();

        if ($languageId !== $this->id && isset($locale->_data['localeDisplayNames'][$languageId])) {
            return $locale->_data['localeDisplayNames'][$languageId];
        }

        if ($locale !== $this) {
            // Try again with this locale
            return $this->getDisplayName($this->id);
        }

        return $this->id;
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
        if ($this->_formatter === null) {
            $config = [
                'locale' => $this->id,
            ];

            if (!Craft::$app->getI18n()->getIsIntlLoaded()) {
                $config['dateTimeFormats'] = $this->_data['dateTimeFormats'];
                $config['standAloneMonthNames'] = $this->_data['standAloneMonthNames'];
                $config['monthNames'] = $this->_data['monthNames'];
                $config['standAloneWeekDayNames'] = $this->_data['standAloneWeekDayNames'];
                $config['weekDayNames'] = $this->_data['weekDayNames'];
                $config['amName'] = $this->_data['amName'];
                $config['pmName'] = $this->_data['pmName'];
                $config['currencySymbols'] = $this->_data['currencySymbols'];
                $config['decimalSeparator'] = $this->getNumberSymbol(self::SYMBOL_DECIMAL_SEPARATOR);
                $config['thousandSeparator'] = $this->getNumberSymbol(self::SYMBOL_GROUPING_SEPARATOR);
                $config['currencyCode'] = $this->getNumberSymbol(self::SYMBOL_INTL_CURRENCY);
            } else {
                $config['dateTimeFormats'] = [
                    'short' => [
                        'date' => $this->getDateFormat(self::LENGTH_SHORT),
                        'time' => $this->getTimeFormat(self::LENGTH_SHORT),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_SHORT),
                    ],
                    'medium' => [
                        'date' => $this->getDateFormat(self::LENGTH_MEDIUM),
                        'time' => $this->getTimeFormat(self::LENGTH_MEDIUM),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_MEDIUM),
                    ],
                    'long' => [
                        'date' => $this->getDateFormat(self::LENGTH_LONG),
                        'time' => $this->getTimeFormat(self::LENGTH_LONG),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_LONG),
                    ],
                    'full' => [
                        'date' => $this->getDateFormat(self::LENGTH_FULL),
                        'time' => $this->getTimeFormat(self::LENGTH_FULL),
                        'datetime' => $this->getDateTimeFormat(self::LENGTH_FULL),
                    ],
                ];
            }

            $this->_formatter = new Formatter($config);
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
    public function getDateFormat(string $length = null, string $format = self::FORMAT_ICU): string
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
    public function getTimeFormat(string $length = null, string $format = self::FORMAT_ICU): string
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
    public function getDateTimeFormat(string $length = null, string $format = self::FORMAT_ICU): string
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
    public function getMonthName(int $month, string $length = null, bool $standAlone = true): string
    {
        if ($length === null) {
            $length = self::LENGTH_FULL;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
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

            return $formatter->format(new DateTime('1970-'.sprintf('%02d', $month).'-01'));
        }

        $which = $standAlone ? 'standAloneMonthNames' : 'monthNames';

        switch ($length) {
            case self::LENGTH_ABBREVIATED:
                return $this->_data[$which]['abbreviated'][$month - 1];
                break; // S
            case self::LENGTH_SHORT:
            case self::LENGTH_MEDIUM:
                return $this->_data[$which]['medium'][$month - 1];
                break; // Sep
            default:
                return $this->_data[$which]['full'][$month - 1];
                break; // September
        }
    }

    /**
     * Returns all of the localized month names.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param bool $standAlone Whether to return the "stand alone" month names.
     * @return array The localized month names.
     */
    public function getMonthNames(string $length = null, bool $standAlone = true): array
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
    public function getWeekDayName(int $day, string $length = null, bool $standAlone = true): string
    {
        if ($length === null) {
            $length = self::LENGTH_FULL;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
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
            return $formatter->format(new DateTime('1970-01-'.sprintf('%02d', $day + 4)));
        }

        $which = $standAlone ? 'standAloneWeekDayNames' : 'weekDayNames';

        switch ($length) {
            case self::LENGTH_ABBREVIATED:
                // T
                return $this->_data[$which]['abbreviated'][$day];
            case self::LENGTH_SHORT:
                // Tu
                return $this->_data[$which]['short'][$day];
            case self::LENGTH_MEDIUM:
                // Tue
                return $this->_data[$which]['medium'][$day];
            default:
                // Tuesday
                return $this->_data[$which]['full'][$day];
        }
    }

    /**
     * Returns all of the localized day of the week names.
     *
     * @param string|null $length The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param bool $standAlone Whether to return the "stand alone" day of the week names.
     * @return array The localized day of the week names.
     */
    public function getWeekDayNames(string $length = null, bool $standAlone = true): array
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
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
        }

        return $this->_data['amName'];
    }

    /**
     * Returns the "PM" name for this locale.
     *
     * @return string The "PM" name.
     */
    public function getPMName(): string
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
        }

        return $this->_data['pmName'];
    }

    // Text Attributes and Symbols
    // -------------------------------------------------------------------------

    /**
     * Returns a text attribute used by this locale.
     *
     * @param int $attribute The attribute to return. Values: Locale::
     * @return string|null The attribute.
     */
    public function getTextAttribute(int $attribute)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);

            return $formatter->getTextAttribute($attribute);
        }

        switch ($attribute) {
            case self::ATTR_POSITIVE_PREFIX:
                return $this->_data['textAttributes']['positivePrefix'];
            case self::ATTR_POSITIVE_SUFFIX:
                return $this->_data['textAttributes']['positiveSuffix'];
            case self::ATTR_NEGATIVE_PREFIX:
                return $this->_data['textAttributes']['negativePrefix'];
            case self::ATTR_NEGATIVE_SUFFIX:
                return $this->_data['textAttributes']['negativeSuffix'];
            case self::ATTR_PADDING_CHARACTER:
                return $this->_data['textAttributes']['paddingCharacter'];
            case self::ATTR_CURRENCY_CODE:
                return $this->_data['textAttributes']['currencyCode'];
            case self::ATTR_DEFAULT_RULESET:
                return $this->_data['textAttributes']['defaultRuleset'];
            case self::ATTR_PUBLIC_RULESETS:
                return $this->_data['textAttributes']['publicRulesets'];
        }

        return null;
    }

    /**
     * Returns a number pattern used by this locale.
     *
     * @param int $style The pattern style to return.
     * Accepted values: Locale::STYLE_DECIMAL, ::STYLE_CURRENCY, ::STYLE_PERCENT, ::STYLE_SCIENTIFIC
     * @return string|null The pattern
     */
    public function getNumberPattern(int $style)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new NumberFormatter($this->id, $style);

            return $formatter->getPattern();
        }

        switch ($style) {
            case self::STYLE_DECIMAL:
                return $this->_data['numberPatterns']['decimal'];
            case self::STYLE_CURRENCY:
                return $this->_data['numberPatterns']['currency'];
            case self::STYLE_PERCENT:
                return $this->_data['numberPatterns']['percent'];
            case self::STYLE_SCIENTIFIC:
                return $this->_data['numberPatterns']['scientific'];
        }

        return null;
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
    public function getNumberSymbol(int $symbol)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);

            return $formatter->getSymbol($symbol);
        }

        switch ($symbol) {
            case self::SYMBOL_DECIMAL_SEPARATOR:
                return $this->_data['numberSymbols']['decimalSeparator'];
            case self::SYMBOL_GROUPING_SEPARATOR:
                return $this->_data['numberSymbols']['groupingSeparator'];
            case self::SYMBOL_PATTERN_SEPARATOR:
                return $this->_data['numberSymbols']['patternSeparator'];
            case self::SYMBOL_PERCENT:
                return $this->_data['numberSymbols']['percent'];
            case self::SYMBOL_ZERO_DIGIT:
                return $this->_data['numberSymbols']['zeroDigit'];
            case self::SYMBOL_DIGIT:
                return $this->_data['numberSymbols']['digit'];
            case self::SYMBOL_MINUS_SIGN:
                return $this->_data['numberSymbols']['minusSign'];
            case self::SYMBOL_PLUS_SIGN:
                return $this->_data['numberSymbols']['plusSign'];
            case self::SYMBOL_CURRENCY:
                return $this->_data['numberSymbols']['currency'];
            case self::SYMBOL_INTL_CURRENCY:
                return $this->_data['numberSymbols']['intlCurrency'];
            case self::SYMBOL_MONETARY_SEPARATOR:
                return $this->_data['numberSymbols']['monetarySeparator'];
            case self::SYMBOL_EXPONENTIAL:
                return $this->_data['numberSymbols']['exponential'];
            case self::SYMBOL_PERMILL:
                return $this->_data['numberSymbols']['permill'];
            case self::SYMBOL_PAD_ESCAPE:
                return $this->_data['numberSymbols']['padEscape'];
            case self::SYMBOL_INFINITY:
                return $this->_data['numberSymbols']['infinity'];
            case self::SYMBOL_NAN:
                return $this->_data['numberSymbols']['nan'];
            case self::SYMBOL_SIGNIFICANT_DIGIT:
                return $this->_data['numberSymbols']['significantDigit'];
            case self::SYMBOL_MONETARY_GROUPING_SEPARATOR:
                return $this->_data['numberSymbols']['monetaryGroupingSeparator'];
        }

        return null;
    }

    /**
     * Returns this locale’s symbol for a given currency.
     *
     * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
     * @return string The currency symbol.
     */
    public function getCurrencySymbol(string $currency): string
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            // hat tip: https://stackoverflow.com/a/30026774
            $formatter = new NumberFormatter("{$this->id}@currency={$currency}", NumberFormatter::CURRENCY);
            return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        }

        if (isset($this->_data['currencySymbols'][$currency])) {
            return $this->_data['currencySymbols'][$currency];
        }

        return $currency;
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Returns the locale ID.
     *
     * @return string
     * @deprecated in 3.0. Use id instead.
     */
    public function getId(): string
    {
        Craft::$app->getDeprecator()->log('Locale::getId()', 'Locale::getId() has been deprecated. Use the id property instead.');

        return $this->id;
    }

    /**
     * Returns the locale name in a given language.
     *
     * @param string|null $targetLocaleId
     * @return string|null
     * @deprecated in 3.0. Use getDisplayName() instead.
     */
    public function getName(string $targetLocaleId = null)
    {
        Craft::$app->getDeprecator()->log('Locale::getName()', 'Locale::getName() has been deprecated. Use getDisplayName() instead.');

        // In Craft 2, getName() with no $targetLocaleId would default to the active language
        if ($targetLocaleId === null) {
            $targetLocaleId = Craft::$app->language;
        }

        return $this->getDisplayName($targetLocaleId);
    }

    /**
     * Returns the locale name in its own language.
     *
     * @return string|false
     * @deprecated in 3.0. Use getDisplayName() instead.
     */
    public function getNativeName()
    {
        Craft::$app->getDeprecator()->log('Locale::getNativeName()', 'Locale::getNativeName() has been deprecated. Use getDisplayName() instead.');

        return $this->getDisplayName();
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a localized date/time format.
     *
     * @param string $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param bool $withDate Whether the date should be included in the format.
     * @param bool $withTime Whether the time should be included in the format.
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     * @return string The date/time format
     */
    private function _getDateTimeFormat(string $length, bool $withDate, bool $withTime, string $format): string
    {
        $icuFormat = $this->_getDateTimeIcuFormat($length, $withDate, $withTime);

        if ($format !== self::FORMAT_ICU) {
            $type = ($withDate ? 'date' : '').($withTime ? 'time' : '');

            switch ($format) {
                case self::FORMAT_PHP:
                    return FormatConverter::convertDateIcuToPhp($icuFormat, $type, $this->id);
                case self::FORMAT_JUI:
                    return FormatConverter::convertDateIcuToJui($icuFormat, $type, $this->id);
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
     * @return string|null The ICU date/time format
     * @throws Exception if $length is invalid
     */
    private function _getDateTimeIcuFormat(string $length, bool $withDate, bool $withTime)
    {
        if ($length === null) {
            $length = self::LENGTH_MEDIUM;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            // Convert length to IntlDateFormatter constants
            switch ($length) {
                case self::LENGTH_FULL:
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $length = IntlDateFormatter::FULL;
                    break;
                case self::LENGTH_LONG:
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $length = IntlDateFormatter::LONG;
                    break;
                case self::LENGTH_MEDIUM:
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $length = IntlDateFormatter::MEDIUM;
                    break;
                case self::LENGTH_SHORT:
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $length = IntlDateFormatter::SHORT;
                    break;
                default:
                    throw new Exception('Invalid date/time format length: '.$length);
            }

            $dateType = ($withDate ? $length : IntlDateFormatter::NONE);
            $timeType = ($withTime ? $length : IntlDateFormatter::NONE);
            $formatter = new IntlDateFormatter($this->id, $dateType, $timeType);
            $pattern = $formatter->getPattern();

            // Use 4-digit year, and no leading zeroes on days/months
            return strtr($pattern, [
                'yyyy' => 'yyyy',
                'yy' => 'yyyy',
                'MMMMM' => 'MMMMM',
                'MMMM' => 'MMMM',
                'MMM' => 'MMM',
                'MM' => 'M',
                'dd' => 'd',
            ]);
        }

        $type = ($withDate ? 'date' : '').($withTime ? 'time' : '');

        switch ($length) {
            case self::LENGTH_SHORT:
                return $this->_data['dateTimeFormats']['short'][$type];
            case self::LENGTH_MEDIUM:
                return $this->_data['dateTimeFormats']['medium'][$type];
            case self::LENGTH_LONG:
                return $this->_data['dateTimeFormats']['long'][$type];
            case self::LENGTH_FULL:
                return $this->_data['dateTimeFormats']['full'][$type];
        }

        return null;
    }
}
