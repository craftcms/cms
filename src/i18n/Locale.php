<?php
/**
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.* _
@license http://buildwithcraft.com/license*/

namespace craft\app\i18n;

use Craft;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use DateTime;
use IntlDateFormatter;
use NumberFormatter;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	 * @var int The short date/time format.
	 */
	const FORMAT_ABBREVIATED = 4;

	/**
	 * @var int The short date/time format.
	 */
	const FORMAT_SHORT = 3;

	/**
	 * @var int The medium date/time format.
	 */
	const FORMAT_MEDIUM = 2;

	/**
	 * @var int The long date/time format.
	 */
	const FORMAT_LONG = 1;

	/**
	 * @var int The full date/time format.
	 */
	const FORMAT_FULL = 0;

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
	 * @throws InvalidParamException If $id is an unsupported locale.
	 */
	public function __construct($id, $config = [])
	{
		$this->id = str_replace('_', '-', $id);

		if (!Craft::$app->getI18n()->getIsIntlLoaded())
		{
			// Load the locale data
			$appDataPath = Craft::$app->getPath()->getAppPath().'/config/locales/'.$this->id.'.php';
			$customDataPath = Craft::$app->getPath()->getConfigPath().'/locales/'.$this->id.'.php';

			if (IOHelper::fileExists($appDataPath))
			{
				$this->data = require($appDataPath);
			}

			if (IOHelper::fileExists($customDataPath))
			{
				if ($this->data !== null)
				{
					$this->data = ArrayHelper::merge($this->data, require($customDataPath));
				}
				else
				{
					$this->data = require($customDataPath);
				}
			}

			if ($this->data === null)
			{
				throw new InvalidParamException('Unsupported locale: '.$this->id);
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
		if (($pos = strpos($this->id, '-')) !== false)
		{
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
		if (($pos = strpos($this->id, '-')) !== false)
		{
			$subTag = explode('-', $this->id);

			// Script sub tags can be distinguished from territory sub tags by length
			if (strlen($subTag[1]) === 4)
			{
				return $subTag[1];
			}
		}
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
		if (($pos = strpos($this->id, '-')) !== false)
		{
			$subTag = explode('-', $this->id);

			// Territory sub tags can be distinguished from script sub tags by length
			if (isset($subTag[2]) && strlen($subTag[2]) < 4)
			{
				return $subTag[2];
			}
			else if (strlen($subTag[1]) < 4)
			{
				return $subTag[1];
			}
		}
	}

	/**
	 * Returns the locale name in a given language.
	 *
	 * @param string|null $inLocale
	 * @return string
	 */
	public function getDisplayName($inLocale = null)
	{
		// If no target locale is specified, default to this locale
		if (!$inLocale)
		{
			$inLocale = $this->id;
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			return \Locale::getDisplayName($this->id, $inLocale);
		}
		else
		{
			if ($inLocale === $this->id)
			{
				$locale = $this;
			}
			else
			{
				try
				{
					$locale = new Locale($inLocale);
				}
				catch (InvalidConfigException $e)
				{
					$locale = $this;
				}
			}

			if (isset($locale->data['localeDisplayNames'][$this->id]))
			{
				return $locale->data['localeDisplayNames'][$this->id];
			}
			else
			{
				// Try just the language
				$languageId = $this->getLanguageID();

				if ($languageId !== $this->id && isset($locale->data['localeDisplayNames'][$languageId]))
				{
					return $locale->data['localeDisplayNames'][$languageId];
				}
			}

			if ($locale !== $this)
			{
				// Try again with this locale
				return $this->getDisplayName($this->id);
			}
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
		if (in_array($this->getLanguageID(), static::$_rtlLanguages))
		{
			return 'rtl';
		}
		else
		{
			return 'ltr';
		}
	}

	/**
	 * Returns a [[Formatter]] for this locale.
	 *
	 * @return Formatter A formatter for this locale.
	 */
	public function getFormatter()
	{
		if ($this->_formatter === null)
		{
			$config = [
				'locale' => $this->id,
			];

			if (!Craft::$app->getI18n()->getIsIntlLoaded())
			{
				$config['dateTimeFormats']        = $this->data['dateTimeFormats'];
				$config['standAloneMonthNames']   = $this->data['standAloneMonthNames'];
				$config['monthNames']             = $this->data['monthNames'];
				$config['standAloneWeekDayNames'] = $this->data['standAloneWeekDayNames'];
				$config['weekDayNames']           = $this->data['weekDayNames'];
				$config['amName']                 = $this->data['amName'];
				$config['pmName']                 = $this->data['pmName'];
				$config['currencySymbols']        = $this->data['currencySymbols'];
				$config['decimalSeparator']       = $this->getNumberSymbol(static::SYMBOL_DECIMAL_SEPARATOR);
				$config['thousandSeparator']      = $this->getNumberSymbol(static::SYMBOL_GROUPING_SEPARATOR);
				$config['currencyCode']           = $this->getNumberSymbol(static::SYMBOL_INTL_CURRENCY);
			}
			else
			{
				$config['dateTimeFormats'] = [
					'short' => [
						'date' => $this->getDateFormat(Locale::FORMAT_SHORT),
						'time' => $this->getTimeFormat(Locale::FORMAT_SHORT),
						'datetime' => $this->getDateTimeFormat(Locale::FORMAT_SHORT),
					],
					'medium' => [
						'date' => $this->getDateFormat(Locale::FORMAT_MEDIUM),
						'time' => $this->getTimeFormat(Locale::FORMAT_MEDIUM),
						'datetime' => $this->getDateTimeFormat(Locale::FORMAT_MEDIUM),
					],
					'long' => [
						'date' => $this->getDateFormat(Locale::FORMAT_LONG),
						'time' => $this->getTimeFormat(Locale::FORMAT_LONG),
						'datetime' => $this->getDateTimeFormat(Locale::FORMAT_LONG),
					],
					'full' => [
						'date' => $this->getDateFormat(Locale::FORMAT_FULL),
						'time' => $this->getTimeFormat(Locale::FORMAT_FULL),
						'datetime' => $this->getDateTimeFormat(Locale::FORMAT_FULL),
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
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized ICU date format.
	 */
	public function getDateFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, true, false);
	}

	/**
	 * Returns the localized ICU time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized ICU time format.
	 */
	public function getTimeFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, false, true);
	}

	/**
	 * Returns the localized ICU date + time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized ICU date + time format.
	 */
	public function getDateTimeFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, true, true);
	}

	/**
	 * Returns a localized month name.
	 *
	 * @param int     $month      The month to return (1-12).
	 * @param int     $length     The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::MEDIUM, ::FULL
	 * @param boolean $standAlone Whether to return the "stand alone" month name.
	 * @return string The localized month name.
	 */
	public function getMonthName($month, $length = null, $standAlone = true)
	{
		if ($length === null)
		{
			$length = static::FORMAT_FULL;
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			$formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: $formatter->setPattern($standAlone ? 'LLLLL' : 'MMMMM'); break; // S
				case static::FORMAT_SHORT:
				case static::FORMAT_MEDIUM:      $formatter->setPattern($standAlone ? 'LLL' : 'MMM'); break;   // Sep
				default:                         $formatter->setPattern($standAlone ? 'LLLL' : 'MMMM'); break;  // September
			}

			return $formatter->format(new DateTime('1970-'.sprintf("%02d", $month).'-01'));
		}
		else
		{
			$which = $standAlone ? 'standAloneMonthNames' : 'monthNames';

			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: return $this->data[$which]['abbreviated'][$month-1]; break; // S
				case static::FORMAT_SHORT:
				case static::FORMAT_MEDIUM:      return $this->data[$which]['medium'][$month-1]; break;      // Sep
				default:                         return $this->data[$which]['full'][$month-1]; break;        // September
			}
		}
	}

	/**
	 * Returns all of the localized month names.
	 *
	 * @param int     $length     The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::MEDIUM, ::FULL
	 * @param boolean $standAlone Whether to return the "stand alone" month names.
	 * @return array The localized month names.
	 */
	public function getMonthNames($length = null, $standAlone = true)
	{
		$monthNames = [];

		for ($month = 1; $month <= 12; $month++)
		{
			$monthNames[] = $this->getMonthName($month, $length, $standAlone);
		}

		return $monthNames;
	}

	/**
	 * Returns a localized day of the week name.
	 *
	 * @param int     $day        The day of the week to return (1-7), where 1 stands for Sunday.
	 * @param int     $length     The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::SHORT, ::MEDIUM, ::FULL
	 * @param boolean $standAlone Whether to return the "stand alone" day of the week name.
	 * @return string The localized day of the week name.
	 */
	public function getWeekDayName($day, $length = null, $standAlone = true)
	{
		if ($length === null)
		{
			$length = static::FORMAT_FULL;
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			$formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: $formatter->setPattern($standAlone ? 'ccccc' : 'eeeee'); break;  // T
				case static::FORMAT_SHORT:       $formatter->setPattern($standAlone ? 'cccccc' : 'eeeeee'); break; // Tu
				case static::FORMAT_MEDIUM:      $formatter->setPattern($standAlone ? 'ccc' : 'eee'); break;    // Tue
				default:                         $formatter->setPattern($standAlone ? 'cccc' : 'eeee'); break;   // Tuesday
			}

			// Jan 1, 1970 was a Thursday
			return $formatter->format(new DateTime('1970-01-'.sprintf("%02d", $day+3)));
		}
		else
		{
			$which = $standAlone ? 'standAloneWeekDayNames' : 'weekDayNames';

			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: return $this->data[$which]['abbreviated'][$day-1]; break; // T
				case static::FORMAT_SHORT:       return $this->data[$which]['short'][$day-1]; break;       // Tu
				case static::FORMAT_MEDIUM:      return $this->data[$which]['medium'][$day-1]; break;      // Tue
				default:                         return $this->data[$which]['full'][$day-1]; break;        // Tuesday
			}
		}
	}

	/**
	 * Returns all of the localized day of the week names.
	 *
	 * @param int     $length The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::MEDIUM, ::FULL
	 * @param boolean $standAlone Whether to return the "stand alone" day of the week names.
	 * @return array The localized day of the week names.
	 */
	public function getWeekDayNames($length = null, $standAlone = true)
	{
		$weekDayNames = [];

		for ($day = 1; $day <= 7; $day++)
		{
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
		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
		}
		else
		{
			return $this->data['amName'];
		}
	}

	/**
	 * Returns the "PM" name for this locale.
	 *
	 * @return string The "PM" name.
	 */
	public function getPMName()
	{
		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
		}
		else
		{
			return $this->data['pmName'];
		}
	}

	// Text Attributes and Symbols
	// -------------------------------------------------------------------------

	/**
	 * Returns a text attribute used by this locale.
	 *
	 * @param int $attribute The attribute to return. Values: Locale::
	 * @return string The attribute.
	 */
	public function getTextAttribute($attribute)
	{
		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			$formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
			return $formatter->getTextAttribute($attribute);
		}
		else
		{
			switch ($attribute)
			{
				case static::ATTR_POSITIVE_PREFIX: return $this->data['textAttributes']['positivePrefix'];
				case static::ATTR_POSITIVE_SUFFIX: return $this->data['textAttributes']['positiveSuffix'];
				case static::ATTR_NEGATIVE_PREFIX: return $this->data['textAttributes']['negativePrefix'];
				case static::ATTR_NEGATIVE_SUFFIX: return $this->data['textAttributes']['negativeSuffix'];
				case static::ATTR_PADDING_CHARACTER: return $this->data['textAttributes']['paddingCharacter'];
				case static::ATTR_CURRENCY_CODE: return $this->data['textAttributes']['currencyCode'];
				case static::ATTR_DEFAULT_RULESET: return $this->data['textAttributes']['defaultRuleset'];
				case static::ATTR_PUBLIC_RULESETS: return $this->data['textAttributes']['publicRulesets'];
			}
		}
	}

	/**
	 * Returns a number symbol used by this locale.
	 *
	 * @param int $symbol The symbol to return. Values: Locale::SYMBOL_DECIMAL_SEPARATOR, ::SYMBOL_GROUPING_SEPARATOR,
	 *                    ::SYMBOL_PATTERN_SEPARATOR, ::SYMBOL_PERCENT, ::SYMBOL_ZERO_DIGIT, ::SYMBOL_DIGIT, ::SYMBOL_MINUS_SIGN,
	 *                    ::SYMBOL_PLUS_SIGN, ::SYMBOL_CURRENCY, ::SYMBOL_INTL_CURRENCY, ::SYMBOL_MONETARY_SEPARATOR,
	 *                    ::SYMBOL_EXPONENTIAL, ::SYMBOL_PERMILL, ::SYMBOL_PAD_ESCAPE, ::SYMBOL_INFINITY, ::SYMBOL_NAN,
	 *                    ::SYMBOL_SIGNIFICANT_DIGIT, ::SYMBOL_MONETARY_GROUPING_SEPARATOR
	 * @return string The symbol.
	 */
	public function getNumberSymbol($symbol)
	{
		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			$formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
			return $formatter->getSymbol($symbol);
		}
		else
		{
			switch ($symbol)
			{
				case static::SYMBOL_DECIMAL_SEPARATOR: return $this->data['numberSymbols']['decimalSeparator'];
				case static::SYMBOL_GROUPING_SEPARATOR: return $this->data['numberSymbols']['groupingSeparator'];
				case static::SYMBOL_PATTERN_SEPARATOR: return $this->data['numberSymbols']['patternSeparator'];
				case static::SYMBOL_PERCENT: return $this->data['numberSymbols']['percent'];
				case static::SYMBOL_ZERO_DIGIT: return $this->data['numberSymbols']['zeroDigit'];
				case static::SYMBOL_DIGIT: return $this->data['numberSymbols']['digit'];
				case static::SYMBOL_MINUS_SIGN: return $this->data['numberSymbols']['minusSign'];
				case static::SYMBOL_PLUS_SIGN: return $this->data['numberSymbols']['plusSign'];
				case static::SYMBOL_CURRENCY: return $this->data['numberSymbols']['currency'];
				case static::SYMBOL_INTL_CURRENCY: return $this->data['numberSymbols']['intlCurrency'];
				case static::SYMBOL_MONETARY_SEPARATOR: return $this->data['numberSymbols']['monetarySeparator'];
				case static::SYMBOL_EXPONENTIAL: return $this->data['numberSymbols']['exponential'];
				case static::SYMBOL_PERMILL: return $this->data['numberSymbols']['permill'];
				case static::SYMBOL_PAD_ESCAPE: return $this->data['numberSymbols']['padEscape'];
				case static::SYMBOL_INFINITY: return $this->data['numberSymbols']['infinity'];
				case static::SYMBOL_NAN: return $this->data['numberSymbols']['nan'];
				case static::SYMBOL_SIGNIFICANT_DIGIT: return $this->data['numberSymbols']['significantDigit'];
				case static::SYMBOL_MONETARY_GROUPING_SEPARATOR: return $this->data['numberSymbols']['monetaryGroupingSeparator'];
			}
		}
	}

	/**
	 * Returns this locale’s symbol for a given currency.
	 *
	 * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
	 * @return string The currency symbol.
	 */
	public function getCurrencySymbol($currency)
	{
		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			// This is way harder than it should be - http://stackoverflow.com/a/28307228/1688568
			$formatter = new NumberFormatter($this->id, NumberFormatter::CURRENCY);
			$withCurrency = $formatter->formatCurrency(0, $currency);
			$formatter->setPattern(str_replace('¤', '', $formatter->getPattern()));
			$withoutCurrency = $formatter->formatCurrency(0, $currency);
			return str_replace($withoutCurrency, '', $withCurrency);
		}
		else if (isset($this->data['currencySymbols'][$currency]))
		{
			return $this->data['currencySymbols'][$currency];
		}
		else
		{
			return $currency;
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a localized ICU date/time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @param boolean $withDate Whether the date should be included in the format.
	 * @param boolean $withTime Whether the time should be included in the format.
	 * @return string The ICU date/time format
	 */
	private function _getDateTimeFormat($length, $withDate, $withTime)
	{
		if ($length === null)
		{
			$length = static::FORMAT_MEDIUM;
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			$dateType = ($withDate ? $length : IntlDateFormatter::NONE);
			$timeType = ($withTime ? $length : IntlDateFormatter::NONE);
			$formatter = new IntlDateFormatter($this->id, $dateType, $timeType);
			$pattern = $formatter->getPattern();

			return strtr($pattern, [
				'yyyy' => 'yyyy',
				'yy'   => 'yyyy'
			]);
		}
		else
		{
			if ($withDate && $withTime)
			{
				$which = 'datetime';
			}
			else if ($withDate)
			{
				$which = 'date';
			}
			else
			{
				$which = 'time';
			}

			switch ($length)
			{
				case static::FORMAT_SHORT:  return StringHelper::replace($this->data['dateTimeFormats']['short'][$which], 'yy', 'Y'); break;
				case static::FORMAT_MEDIUM: return $this->data['dateTimeFormats']['medium'][$which]; break;
				case static::FORMAT_LONG:   return $this->data['dateTimeFormats']['long'][$which]; break;
				case static::FORMAT_FULL:   return $this->data['dateTimeFormats']['full'][$which]; break;
			}
		}
	}
}
