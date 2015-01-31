<?php
/**
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.* _
@license http://buildwithcraft.com/license*/

namespace craft\app\i18n;

use Craft;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\IOHelper;
use IntlDateFormatter;
use NumberFormatter;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\i18n\Formatter;

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
	 * @var The locale ID.
	 */
	public $id;

	/**
	 * @var boolean Whether the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 */
	private $_intlLoaded = false;

	/**
	 * @var array The configured locale data, used if the [PHP intl extension](http://php.net/manual/en/book.intl.php) isn’t loaded.
	 */
	private $_data;

	/**
	 * @var The locale's formatter.
	 */
	private $_formatter;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param int   $id     The locale ID.
	 * @param array $config Name-value pairs that will be used to initialize the object properties.
	 */
	public function __construct($id, $config = [])
	{
		$this->id = $id;
		$this->_intlLoaded = extension_loaded('intl');

		if (!$this->_intlLoaded)
		{
			// Load the locale data
			$appDataPath = Craft::$app->path->getAppPath().'/config/locales/'.$this->id;
			$customDataPath = Craft::$app->path->getConfigPath().'/locales/'.$this->id;

			if (IOHelper::fileExists($appDataPath))
			{
				$this->_data = require($appDataPath);
			}

			if (IOHelper::fileExists($customDataPath))
			{
				if ($this->_data !== null)
				{
					$this->_data = ArrayHelper::merge($this->_data, require($customDataPath));
				}
				else
				{
					$this->_data = require($customDataPath);
				}
			}

			if ($this->_data === null)
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

		if ($this->_intlLoaded)
		{
			return \Locale::getDisplayName($this->id, $inLocale);
		}
		else if ($this->id === 'en')
		{
			return 'English';
		}
		else
		{
			return $this->id;
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

			if (!$this->_intlLoaded)
			{
				$config['dateFormat']        = 'php:'.$this->getDateFormat();
				$config['timeFormat']        = 'php:'.$this->getTimeFormat();
				$config['datetimeFormat']    = 'php:'.$this->getDateTimeFormat();
				$config['thousandSeparator'] = $this->getNumberSymbol(static::SYMBOL_GROUPING_SEPARATOR);
				$config['currencyCode']      = $this->getNumberSymbol(static::SYMBOL_CURRENCY);
			}

			$this->_formatter = new Formatter($config);
		}

		return $this->_formatter;
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
		if ($this->_intlLoaded)
		{
			$formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
			return $formatter->getSymbol($symbol);
		}
		else
		{
			switch ($symbol)
			{
				case static::SYMBOL_DECIMAL_SEPARATOR: return $this->_data['numberSymbols']['decimalSeparator'];
				case static::SYMBOL_GROUPING_SEPARATOR: return $this->_data['numberSymbols']['groupingSeparator'];
				case static::SYMBOL_PATTERN_SEPARATOR: return $this->_data['numberSymbols']['patternSeparator'];
				case static::SYMBOL_PERCENT: return $this->_data['numberSymbols']['percent'];
				case static::SYMBOL_ZERO_DIGIT: return $this->_data['numberSymbols']['zeroDigit'];
				case static::SYMBOL_DIGIT: return $this->_data['numberSymbols']['digit'];
				case static::SYMBOL_MINUS_SIGN: return $this->_data['numberSymbols']['minusSign'];
				case static::SYMBOL_PLUS_SIGN: return $this->_data['numberSymbols']['plusSign'];
				case static::SYMBOL_CURRENCY: return $this->_data['numberSymbols']['currency'];
				case static::SYMBOL_INTL_CURRENCY: return $this->_data['numberSymbols']['intlCurrency'];
				case static::SYMBOL_MONETARY_SEPARATOR: return $this->_data['numberSymbols']['monetarySeparator'];
				case static::SYMBOL_EXPONENTIAL: return $this->_data['numberSymbols']['exponential'];
				case static::SYMBOL_PERMILL: return $this->_data['numberSymbols']['permill'];
				case static::SYMBOL_PAD_ESCAPE: return $this->_data['numberSymbols']['padEscape'];
				case static::SYMBOL_INFINITY: return $this->_data['numberSymbols']['infinity'];
				case static::SYMBOL_NAN: return $this->_data['numberSymbols']['nan'];
				case static::SYMBOL_SIGNIFICANT_DIGIT: return $this->_data['numberSymbols']['significantDigit'];
				case static::SYMBOL_MONETARY_GROUPING_SEPARATOR: return $this->_data['numberSymbols']['monetaryGroupingSeparator'];
			}
		}
	}

	/**
	 * Returns the localized PHP date format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized PHP date format.
	 */
	public function getDateFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, true, false);
	}

	/**
	 * Returns the localized PHP time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized PHP time format.
	 */
	public function getTimeFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, false, true);
	}

	/**
	 * Returns the localized PHP date + time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized PHP date + time format.
	 */
	public function getDateTimeFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, true, true);
	}

	/**
	 * Returns the "AM" name for this locale.
	 *
	 * @return string The "AM" name.
	 */
	public function getAMName()
	{
		return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
	}

	/**
	 * Returns the "PM" name for this locale.
	 *
	 * @return string The "PM" name.
	 */
	public function getPMName()
	{
		return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
	}

	/**
	 * Returns a localized PHP date/time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @param boolean $withDate Whether the date should be included in the format.
	 * @param boolean $withTime Whether the time should be included in the format.
	 * @return string The PHP date/time format
	 */
	private function _getDateTimeFormat($length, $withDate, $withTime)
	{
		if ($length === null)
		{
			$length = static::FORMAT_MEDIUM;
		}

		if ($this->_intlLoaded)
		{
			$dateType = ($withDate ? $length : IntlDateFormatter::NONE);
			$timeType = ($withTime ? $length : IntlDateFormatter::NONE);
			$formatter = new IntlDateFormatter($this->id, $dateType, $timeType, Craft::$app->timeZone);
			return $formatter->getPattern();
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
				case static::FORMAT_SHORT:  return $this->_data['dateFormats']['short'][$which];
				case static::FORMAT_MEDIUM: return $this->_data['dateFormats']['medium'][$which];
				case static::FORMAT_LONG:   return $this->_data['dateFormats']['long'][$which];
				case static::FORMAT_FULL:   return $this->_data['dateFormats']['full'][$which];
			}
		}
	}
}
