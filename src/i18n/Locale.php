<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\i18n;

use Craft;
use craft\app\helpers\Localization;
use DateTime;
use IntlDateFormatter;
use NumberFormatter;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\helpers\FormatConverter;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Locale extends Object
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
     * @var int The abbreviated date/time format.
     */
    const LENGTH_ABBREVIATED = 'abbreviated';

    /**
     * @var int The short date/time format.
     */
    const LENGTH_SHORT = 'short';

    /**
     * @var int The medium date/time format.
     */
    const LENGTH_MEDIUM = 'medium';

    /**
     * @var int The long date/time format.
     */
    const LENGTH_LONG = 'long';

    /**
     * @var int The full date/time format.
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
     * @var string The locale ID.
     */
    public $id;

    /**
     * @var array The configured locale data, used if the [PHP intl extension](http://php.net/manual/en/book.intl.php) isn’t loaded.
     */
    private $data;

    /**
     * @var Formatter The locale's formatter.
     */
    private $_formatter;

    // Public Methods
    // =========================================================================

    /**
     * Constructor.
     *
     * @param string $id     The locale ID.
     * @param array  $config Name-value pairs that will be used to initialize the object properties.
     *
     * @throws InvalidParamException If $id is an unsupported locale.
     */
    public function __construct($id, $config = [])
    {
        if (strpos($id, '_') !== false) {
            $id = str_replace('_', '-', $id);
        }

        $this->id = $id;

        if (!Craft::$app->getI18n()->getIsIntlLoaded()) {
            $this->data = Localization::getLocaleData($this->id);

            if ($this->data === null) {
                $this->data = Localization::getLocaleData('en-US');
            }
        }

        parent::__construct($config);
    }

    /**
     * Use the ID as the string representation of locales.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }

    /**
     * Returns this locale’s language ID.
     *
     * @return string This locale’s language ID.
     */
    public function getLanguageID()
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
    public function getScriptID()
    {
        // Find sub tags
        if (($pos = strpos($this->id, '-')) !== false) {
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
    public function getTerritoryID()
    {
        // Find sub tags
        if (($pos = strpos($this->id, '-')) !== false) {
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
     *
     * @return string
     */
    public function getDisplayName($inLocale = null)
    {
        // If no target locale is specified, default to this locale
        if (!$inLocale) {
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

        if (isset($locale->data['localeDisplayNames'][$this->id])) {
            return $locale->data['localeDisplayNames'][$this->id];
        }

        // Try just the language
        $languageId = $this->getLanguageID();

        if ($languageId !== $this->id && isset($locale->data['localeDisplayNames'][$languageId])) {
            return $locale->data['localeDisplayNames'][$languageId];
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
    public function getOrientation()
    {
        if (in_array($this->getLanguageID(), static::$_rtlLanguages)) {
            return 'rtl';
        }

        return 'ltr';
    }

    /**
     * Returns a [[Formatter]] for this locale.
     *
     * @return Formatter A formatter for this locale.
     */
    public function getFormatter()
    {
        if ($this->_formatter === null) {
            $config = [
                'locale' => $this->id,
            ];

            if (!Craft::$app->getI18n()->getIsIntlLoaded()) {
                $config['dateTimeFormats'] = $this->data['dateTimeFormats'];
                $config['standAloneMonthNames'] = $this->data['standAloneMonthNames'];
                $config['monthNames'] = $this->data['monthNames'];
                $config['standAloneWeekDayNames'] = $this->data['standAloneWeekDayNames'];
                $config['weekDayNames'] = $this->data['weekDayNames'];
                $config['amName'] = $this->data['amName'];
                $config['pmName'] = $this->data['pmName'];
                $config['currencySymbols'] = $this->data['currencySymbols'];
                $config['decimalSeparator'] = $this->getNumberSymbol(static::SYMBOL_DECIMAL_SEPARATOR);
                $config['thousandSeparator'] = $this->getNumberSymbol(static::SYMBOL_GROUPING_SEPARATOR);
                $config['currencyCode'] = $this->getNumberSymbol(static::SYMBOL_INTL_CURRENCY);
            } else {
                $config['dateTimeFormats'] = [
                    'short' => [
                        'date' => $this->getDateFormat(Locale::LENGTH_SHORT),
                        'time' => $this->getTimeFormat(Locale::LENGTH_SHORT),
                        'datetime' => $this->getDateTimeFormat(Locale::LENGTH_SHORT),
                    ],
                    'medium' => [
                        'date' => $this->getDateFormat(Locale::LENGTH_MEDIUM),
                        'time' => $this->getTimeFormat(Locale::LENGTH_MEDIUM),
                        'datetime' => $this->getDateTimeFormat(Locale::LENGTH_MEDIUM),
                    ],
                    'long' => [
                        'date' => $this->getDateFormat(Locale::LENGTH_LONG),
                        'time' => $this->getTimeFormat(Locale::LENGTH_LONG),
                        'datetime' => $this->getDateTimeFormat(Locale::LENGTH_LONG),
                    ],
                    'full' => [
                        'date' => $this->getDateFormat(Locale::LENGTH_FULL),
                        'time' => $this->getTimeFormat(Locale::LENGTH_FULL),
                        'datetime' => $this->getDateTimeFormat(Locale::LENGTH_FULL),
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
     * @param string $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     *
     * @return string The localized ICU date format.
     */
    public function getDateFormat($length = null, $format = self::FORMAT_ICU)
    {
        return $this->_getDateTimeFormat($length, true, false, $format);
    }

    /**
     * Returns the localized ICU time format.
     *
     * @param string $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     *
     * @return string The localized ICU time format.
     */
    public function getTimeFormat($length = null, $format = self::FORMAT_ICU)
    {
        return $this->_getDateTimeFormat($length, false, true, $format);
    }

    /**
     * Returns the localized ICU date + time format.
     *
     * @param string $length The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param string $format The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     *
     * @return string The localized ICU date + time format.
     */
    public function getDateTimeFormat($length = null, $format = self::FORMAT_ICU)
    {
        return $this->_getDateTimeFormat($length, true, true, $format);
    }

    /**
     * Returns a localized month name.
     *
     * @param integer $month      The month to return (1-12).
     * @param string  $length     The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param boolean $standAlone Whether to return the "stand alone" month name.
     *
     * @return string The localized month name.
     */
    public function getMonthName($month, $length = null, $standAlone = true)
    {
        if ($length === null) {
            $length = static::LENGTH_FULL;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

            switch ($length) {
                case static::LENGTH_ABBREVIATED:
                    $formatter->setPattern($standAlone ? 'LLLLL' : 'MMMMM');
                    break; // S
                case static::LENGTH_SHORT:
                case static::LENGTH_MEDIUM:
                    $formatter->setPattern($standAlone ? 'LLL' : 'MMM');
                    break;   // Sep
                default:
                    $formatter->setPattern($standAlone ? 'LLLL' : 'MMMM');
                    break;  // September
            }

            return $formatter->format(new DateTime('1970-'.sprintf("%02d",
                    $month).'-01'));
        } else {
            $which = $standAlone ? 'standAloneMonthNames' : 'monthNames';

            switch ($length) {
                case static::LENGTH_ABBREVIATED:
                    return $this->data[$which]['abbreviated'][$month - 1];
                    break; // S
                case static::LENGTH_SHORT:
                case static::LENGTH_MEDIUM:
                    return $this->data[$which]['medium'][$month - 1];
                    break;      // Sep
                default:
                    return $this->data[$which]['full'][$month - 1];
                    break;        // September
            }
        }
    }

    /**
     * Returns all of the localized month names.
     *
     * @param string  $length     The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param boolean $standAlone Whether to return the "stand alone" month names.
     *
     * @return array The localized month names.
     */
    public function getMonthNames($length = null, $standAlone = true)
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
     * @param integer $day        The day of the week to return (0-6), where 0 stands for Sunday.
     * @param string  $length     The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::SHORT, ::MEDIUM, ::FULL
     * @param boolean $standAlone Whether to return the "stand alone" day of the week name.
     *
     * @return string The localized day of the week name.
     */
    public function getWeekDayName($day, $length = null, $standAlone = true)
    {
        if ($length === null) {
            $length = static::LENGTH_FULL;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

            switch ($length) {
                case static::LENGTH_ABBREVIATED:
                    // T
                    $formatter->setPattern($standAlone ? 'ccccc' : 'eeeee');
                    break;
                case static::LENGTH_SHORT:
                    // Tu
                    $formatter->setPattern($standAlone ? 'cccccc' : 'eeeeee');
                    break;
                case static::LENGTH_MEDIUM:
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
            return $formatter->format(new DateTime('1970-01-'.sprintf("%02d", $day + 4)));
        } else {
            $which = $standAlone ? 'standAloneWeekDayNames' : 'weekDayNames';

            switch ($length) {
                case static::LENGTH_ABBREVIATED:
                    // T
                    return $this->data[$which]['abbreviated'][$day];
                case static::LENGTH_SHORT:
                    // Tu
                    return $this->data[$which]['short'][$day];
                case static::LENGTH_MEDIUM:
                    // Tue
                    return $this->data[$which]['medium'][$day];
                default:
                    // Tuesday
                    return $this->data[$which]['full'][$day];
            }
        }
    }

    /**
     * Returns all of the localized day of the week names.
     *
     * @param string  $length     The format length that should be returned. Values: Locale::LENGTH_ABBREVIATED, ::MEDIUM, ::FULL
     * @param boolean $standAlone Whether to return the "stand alone" day of the week names.
     *
     * @return array The localized day of the week names.
     */
    public function getWeekDayNames($length = null, $standAlone = true)
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
    public function getAMName()
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
        }

        return $this->data['amName'];
    }

    /**
     * Returns the "PM" name for this locale.
     *
     * @return string The "PM" name.
     */
    public function getPMName()
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
        }

        return $this->data['pmName'];
    }

    // Text Attributes and Symbols
    // -------------------------------------------------------------------------

    /**
     * Returns a text attribute used by this locale.
     *
     * @param integer $attribute The attribute to return. Values: Locale::
     *
     * @return string The attribute.
     */
    public function getTextAttribute($attribute)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);

            return $formatter->getTextAttribute($attribute);
        }

        switch ($attribute) {
            case static::ATTR_POSITIVE_PREFIX:
                return $this->data['textAttributes']['positivePrefix'];
            case static::ATTR_POSITIVE_SUFFIX:
                return $this->data['textAttributes']['positiveSuffix'];
            case static::ATTR_NEGATIVE_PREFIX:
                return $this->data['textAttributes']['negativePrefix'];
            case static::ATTR_NEGATIVE_SUFFIX:
                return $this->data['textAttributes']['negativeSuffix'];
            case static::ATTR_PADDING_CHARACTER:
                return $this->data['textAttributes']['paddingCharacter'];
            case static::ATTR_CURRENCY_CODE:
                return $this->data['textAttributes']['currencyCode'];
            case static::ATTR_DEFAULT_RULESET:
                return $this->data['textAttributes']['defaultRuleset'];
            case static::ATTR_PUBLIC_RULESETS:
                return $this->data['textAttributes']['publicRulesets'];
        }

        return null;
    }

    /**
     * Returns a number pattern used by this locale.
     *
     * @param integer $style The pattern style to return.
     *                       Accepted values: Locale::STYLE_DECIMAL, ::STYLE_CURRENCY, ::STYLE_PERCENT, ::STYLE_SCIENTIFIC
     *
     * @return string The pattern
     */
    public function getNumberPattern($style)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new NumberFormatter($this->id, $style);

            return $formatter->getPattern();
        }

        switch ($style) {
            case static::STYLE_DECIMAL:
                return $this->data['numberPatterns']['decimal'];
            case static::STYLE_CURRENCY:
                return $this->data['numberPatterns']['currency'];
            case static::STYLE_PERCENT:
                return $this->data['numberPatterns']['percent'];
            case static::STYLE_SCIENTIFIC:
                return $this->data['numberPatterns']['scientific'];
        }

        return null;
    }

    /**
     * Returns a number symbol used by this locale.
     *
     * @param integer $symbol The symbol to return.
     *                        Accepted values: Locale::SYMBOL_DECIMAL_SEPARATOR, ::SYMBOL_GROUPING_SEPARATOR,
     *                        ::SYMBOL_PATTERN_SEPARATOR, ::SYMBOL_PERCENT, ::SYMBOL_ZERO_DIGIT, ::SYMBOL_DIGIT, ::SYMBOL_MINUS_SIGN,
     *                        ::SYMBOL_PLUS_SIGN, ::SYMBOL_CURRENCY, ::SYMBOL_INTL_CURRENCY, ::SYMBOL_MONETARY_SEPARATOR,
     *                        ::SYMBOL_EXPONENTIAL, ::SYMBOL_PERMILL, ::SYMBOL_PAD_ESCAPE, ::SYMBOL_INFINITY, ::SYMBOL_NAN,
     *                        ::SYMBOL_SIGNIFICANT_DIGIT, ::SYMBOL_MONETARY_GROUPING_SEPARATOR
     *
     * @return string The symbol.
     */
    public function getNumberSymbol($symbol)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            $formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);

            return $formatter->getSymbol($symbol);
        }

        switch ($symbol) {
            case static::SYMBOL_DECIMAL_SEPARATOR:
                return $this->data['numberSymbols']['decimalSeparator'];
            case static::SYMBOL_GROUPING_SEPARATOR:
                return $this->data['numberSymbols']['groupingSeparator'];
            case static::SYMBOL_PATTERN_SEPARATOR:
                return $this->data['numberSymbols']['patternSeparator'];
            case static::SYMBOL_PERCENT:
                return $this->data['numberSymbols']['percent'];
            case static::SYMBOL_ZERO_DIGIT:
                return $this->data['numberSymbols']['zeroDigit'];
            case static::SYMBOL_DIGIT:
                return $this->data['numberSymbols']['digit'];
            case static::SYMBOL_MINUS_SIGN:
                return $this->data['numberSymbols']['minusSign'];
            case static::SYMBOL_PLUS_SIGN:
                return $this->data['numberSymbols']['plusSign'];
            case static::SYMBOL_CURRENCY:
                return $this->data['numberSymbols']['currency'];
            case static::SYMBOL_INTL_CURRENCY:
                return $this->data['numberSymbols']['intlCurrency'];
            case static::SYMBOL_MONETARY_SEPARATOR:
                return $this->data['numberSymbols']['monetarySeparator'];
            case static::SYMBOL_EXPONENTIAL:
                return $this->data['numberSymbols']['exponential'];
            case static::SYMBOL_PERMILL:
                return $this->data['numberSymbols']['permill'];
            case static::SYMBOL_PAD_ESCAPE:
                return $this->data['numberSymbols']['padEscape'];
            case static::SYMBOL_INFINITY:
                return $this->data['numberSymbols']['infinity'];
            case static::SYMBOL_NAN:
                return $this->data['numberSymbols']['nan'];
            case static::SYMBOL_SIGNIFICANT_DIGIT:
                return $this->data['numberSymbols']['significantDigit'];
            case static::SYMBOL_MONETARY_GROUPING_SEPARATOR:
                return $this->data['numberSymbols']['monetaryGroupingSeparator'];
        }

        return null;
    }

    /**
     * Returns this locale’s symbol for a given currency.
     *
     * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
     *
     * @return string The currency symbol.
     */
    public function getCurrencySymbol($currency)
    {
        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            // see http://stackoverflow.com/a/28307228/1688568
            $formatter = new NumberFormatter($this->id, NumberFormatter::CURRENCY);
            $formatter->setPattern('¤');
            $formatter->setAttribute(NumberFormatter::MAX_SIGNIFICANT_DIGITS, 0);
            $formattedPrice = $formatter->formatCurrency(0, $currency);
            $zero = $formatter->getSymbol(NumberFormatter::ZERO_DIGIT_SYMBOL);
            $currencySymbol = str_replace($zero, '', $formattedPrice);

            return $currencySymbol;
        }

        if (isset($this->data['currencySymbols'][$currency])) {
            return $this->data['currencySymbols'][$currency];
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
    public function getId()
    {
        Craft::$app->getDeprecator()->log('Locale::getId()', 'Locale::getId() has been deprecated. Use the id property instead.');

        return $this->id;
    }

    /**
     * Returns the locale name in a given language.
     *
     * @param string|null $targetLocaleId
     *
     * @return string|null
     * @deprecated in 3.0. Use getDisplayName() instead.
     */
    public function getName($targetLocaleId = null)
    {
        Craft::$app->getDeprecator()->log('Locale::getName()', 'Locale::getName() has been deprecated. Use getDisplayName() instead.');

        // In Craft 2, getName() with no $targetLocaleId would default to the active language
        if (!$targetLocaleId) {
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
     * @param string  $length   The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param boolean $withDate Whether the date should be included in the format.
     * @param boolean $withTime Whether the time should be included in the format.
     * @param string  $format   The format type that should be returned. Values: Locale::FORMAT_ICU (default), ::FORMAT_PHP, ::FORMAT_JUI
     *
     * @return string The date/time format
     */
    private function _getDateTimeFormat($length, $withDate, $withTime, $format)
    {
        $icuFormat = $this->_getDateTimeIcuFormat($length, $withDate, $withTime);

        if ($format != self::FORMAT_ICU) {
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
     * @param string  $length   The format length that should be returned. Values: Locale::LENGTH_SHORT, ::MEDIUM, ::LONG, ::FULL
     * @param boolean $withDate Whether the date should be included in the format.
     * @param boolean $withTime Whether the time should be included in the format.
     *
     * @return string The ICU date/time format
     * @throws Exception if $length is invalid
     */
    private function _getDateTimeIcuFormat($length, $withDate, $withTime)
    {
        if ($length === null) {
            $length = static::LENGTH_MEDIUM;
        }

        if (Craft::$app->getI18n()->getIsIntlLoaded()) {
            // Convert length to IntlDateFormatter constants
            switch ($length) {
                case self::LENGTH_FULL:
                    $length = IntlDateFormatter::FULL;
                    break;
                case self::LENGTH_LONG:
                    $length = IntlDateFormatter::LONG;
                    break;
                case self::LENGTH_MEDIUM:
                    $length = IntlDateFormatter::MEDIUM;
                    break;
                case self::LENGTH_SHORT:
                    $length = IntlDateFormatter::SHORT;
                    break;
                default:
                    throw new Exception('Invalid date/time format length: '.$length);
            }

            $dateType = ($withDate ? $length : IntlDateFormatter::NONE);
            $timeType = ($withTime ? $length : IntlDateFormatter::NONE);
            $formatter = new IntlDateFormatter($this->id, $dateType, $timeType);
            $pattern = $formatter->getPattern();

            // Use 4-digit year
            return strtr($pattern, [
                'yyyy' => 'yyyy',
                'yy' => 'yyyy'
            ]);
        }

        $type = ($withDate ? 'date' : '').($withTime ? 'time' : '');

        switch ($length) {
            case static::LENGTH_SHORT:
                return $this->data['dateTimeFormats']['short'][$type];
            case static::LENGTH_MEDIUM:
                return $this->data['dateTimeFormats']['medium'][$type];
            case static::LENGTH_LONG:
                return $this->data['dateTimeFormats']['long'][$type];
            case static::LENGTH_FULL:
                return $this->data['dateTimeFormats']['full'][$type];
        }

        return null;
    }
}
