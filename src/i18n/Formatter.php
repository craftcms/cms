<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use DateTime;
use DateTimeZone;
use NumberFormatter;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\FormatConverter;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Formatter extends \yii\i18n\Formatter
{
	// Properties
	// =========================================================================

	/**
	 * @var array The locale’s date/time formats.
	 */
	public $dateTimeFormats;

	/**
	 * @var array The localized "stand alone" month names.
	 */
	public $standAloneMonthNames;

	/**
	 * @var array The localized month names.
	 */
	public $monthNames;

	/**
	 * @var array The localized "stand alone" day of the week names.
	 */
	public $standAloneWeekDayNames;

	/**
	 * @var array The localized day of the week names.
	 */
	public $weekDayNames;

	/**
	 * @var string The localized AM name.
	 */
	public $amName;

	/**
	 * @var string The localized PM name.
	 */
	public $pmName;

	/**
	 * @var array The locale's currency symbols.
	 */
	public $currencySymbols;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @param integer|string|DateTime $value
	 * @param string $format
	 * @return string
	 * @throws InvalidParamException
	 * @throws InvalidConfigException
	 */
	public function asDate($value, $format = null)
	{
		if ($format === null)
		{
			$format = $this->dateFormat;
		}

		if (isset($this->dateTimeFormats[$format]['date']))
		{
			$format = $this->dateTimeFormats[$format]['date'];
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			return parent::asDate($value, $format);
		}
		else
		{
			return $this->_formatDateTimeValue($value, $format, 'date');
		}
	}

	/**
	 * @inheritdoc
	 *
	 * @param integer|string|DateTime $value
	 * @param string $format
	 * @return string
	 * @throws InvalidParamException
	 * @throws InvalidConfigException
	 */
	public function asTime($value, $format = null)
	{
		if ($format === null)
		{
			$format = $this->timeFormat;
		}

		if (isset($this->dateTimeFormats[$format]['time']))
		{
			$format = $this->dateTimeFormats[$format]['time'];
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			return parent::asTime($value, $format);
		}
		else
		{
			return $this->_formatDateTimeValue($value, $format, 'time');
		}
	}

	/**
	 * @inheritdoc
	 *
	 * @param integer|string|DateTime $value
	 * @param string $format
	 * @return string
	 * @throws InvalidParamException
	 * @throws InvalidConfigException
	 */
	public function asDatetime($value, $format = null)
	{
		if ($format === null)
		{
			$format = $this->datetimeFormat;
		}

		if (isset($this->dateTimeFormats[$format]['datetime']))
		{
			$format = $this->dateTimeFormats[$format]['datetime'];
		}

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			return parent::asDatetime($value, $format);
		}
		else
		{
			return $this->_formatDateTimeValue($value, $format, 'datetime');
		}
	}

	/**
	 * Formats the value as a currency number.
	 *
	 * This function does not requires the [PHP intl extension](http://php.net/manual/en/book.intl.php) to be installed
	 * to work but it is highly recommended to install it to get good formatting results.
	 *
	 * @param mixed $value the value to be formatted.
	 * @param string $currency the 3-letter ISO 4217 currency code indicating the currency to use.
	 * If null, [[currencyCode]] will be used.
	 * @param array $options optional configuration for the number formatter. This parameter will be merged with [[numberFormatterOptions]].
	 * @param array $textOptions optional configuration for the number formatter. This parameter will be merged with [[numberFormatterTextOptions]].
	 * @param boolean $omitIntegerDecimals Whether the formatted currency should omit the decimals if the value given is an integer.
	 * @return string the formatted result.
	 * @throws InvalidParamException if the input value is not numeric.
	 * @throws InvalidConfigException if no currency is given and [[currencyCode]] is not defined.
	 */
	public function asCurrency($value, $currency = null, $options = [], $textOptions = [], $omitIntegerDecimals = false)
	{
		$omitDecimals = ($omitIntegerDecimals && (int)$value == $value);

		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			if ($omitDecimals)
			{
				$options[NumberFormatter::MAX_FRACTION_DIGITS] = 0;
				$options[NumberFormatter::MIN_FRACTION_DIGITS] = 0;
			}

			return parent::asCurrency($value, $currency, $options, $textOptions);
		}
		else
		{
			// Code adapted from \yii\i18n\Formatter
			if ($value === null)
			{
				return $this->nullDisplay;
			}

			$value = $this->normalizeNumericValue($value);

			if ($currency === null)
			{
				if ($this->currencyCode === null)
				{
					throw new InvalidConfigException('The default currency code for the formatter is not defined.');
				}

				$currency = $this->currencyCode;
			}

			// Do we have a localized symbol for this currency?
			if (isset($this->currencySymbols[$currency]))
			{
				$currency = $this->currencySymbols[$currency];
			}

			$decimals = $omitDecimals ? 0 : 2;
			return $currency.$this->asDecimal($value, $decimals, $options, $textOptions);
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Formats a given date/time.
	 *
	 * Code mostly copied from [[parent::formatDateTimeValue()]], with the exception that translatable strings
	 * in the date/time format will be returned in the correct locale.
	 *
	 * @param integer|string|DateTime $value The value to be formatted. The following
	 * types of value are supported:
	 *
	 * - an integer representing a UNIX timestamp
	 * - a string that can be [parsed to create a DateTime object](http://php.net/manual/en/datetime.formats.php).
	 *   The timestamp is assumed to be in [[defaultTimeZone]] unless a time zone is explicitly given.
	 * - a PHP [DateTime](http://php.net/manual/en/class.datetime.php) object
	 *
	 * @param string $format The format used to convert the value into a date string.
	 * @param string $type 'date', 'time', or 'datetime'.
	 * @throws InvalidConfigException if the date format is invalid.
	 * @return string the formatted result.
	 */
	private function _formatDateTimeValue($value, $format, $type)
	{
		$timeZone = $this->timeZone;

		// Avoid time zone conversion for date-only values
		if ($type === 'date')
		{
			list($timestamp, $hasTimeInfo) = $this->normalizeDatetimeValue($value, true);

			if (!$hasTimeInfo)
			{
				$timeZone = $this->defaultTimeZone;
			}
		}
		else
		{
			$timestamp = $this->normalizeDatetimeValue($value);
		}

		if ($timestamp === null)
		{
			return $this->nullDisplay;
		}

		if (strncmp($format, 'php:', 4) === 0)
		{
			$format = substr($format, 4);
			$format = FormatConverter::convertDatePhpToIcu($format, $type, $this->locale);
		}

		if ($timeZone != null)
		{
			if ($timestamp instanceof \DateTimeImmutable)
			{
				$timestamp = $timestamp->setTimezone(new DateTimeZone($timeZone));
			}
			else
			{
				$timestamp->setTimezone(new DateTimeZone($timeZone));
			}
		}

		// Parse things that we can translate before passing it off to DateTime::format()
		$tr = [];

		if (preg_match_all('/(?<!\')\'.*?[^\']\'(?!\')/', $format, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$tr[$match] = $match;
			}
		}

		if ($this->standAloneMonthNames !== null || $this->monthNames !== null)
		{
			$month = $timestamp->format('n') - 1;

			if ($this->standAloneMonthNames !== null)
			{
				$tr['LLLLL'] = '\''.$this->standAloneMonthNames['abbreviated'][$month].'\'';
				$tr['LLLL']  = '\''.$this->standAloneMonthNames['full'][$month].'\'';
				$tr['LLL']   = '\''.$this->standAloneMonthNames['medium'][$month].'\'';
			}

			if ($this->monthNames !== null)
			{
				$tr['MMMMM'] = '\''.$this->monthNames['abbreviated'][$month].'\'';
				$tr['MMMM']  = '\''.$this->monthNames['full'][$month].'\'';
				$tr['MMM']   = '\''.$this->monthNames['medium'][$month].'\'';
			}
		}

		if ($this->standAloneWeekDayNames !== null || $this->weekDayNames !== null)
		{
			$day = $timestamp->format('w');

			if ($this->standAloneWeekDayNames !== null)
			{
				$tr['cccccc'] = '\''.$this->standAloneWeekDayNames['short'][$day].'\'';
				$tr['ccccc']  = '\''.$this->standAloneWeekDayNames['abbreviated'][$day].'\'';
				$tr['cccc']   = '\''.$this->standAloneWeekDayNames['full'][$day].'\'';
				$tr['ccc']    = '\''.$this->standAloneWeekDayNames['medium'][$day].'\'';
			}

			if ($this->weekDayNames !== null)
			{
				$tr['EEEEEE'] = '\''.$this->weekDayNames['short'][$day].'\'';
				$tr['EEEEE']  = '\''.$this->weekDayNames['abbreviated'][$day].'\'';
				$tr['EEEE']   = '\''.$this->weekDayNames['full'][$day].'\'';
				$tr['EEE']    = '\''.$this->weekDayNames['medium'][$day].'\'';
				$tr['EE']     = '\''.$this->weekDayNames['medium'][$day].'\'';
				$tr['E']      = '\''.$this->weekDayNames['medium'][$day].'\'';

				$tr['eeeeee'] = '\''.$this->weekDayNames['short'][$day].'\'';
				$tr['eeeee']  = '\''.$this->weekDayNames['abbreviated'][$day].'\'';
				$tr['eeee']   = '\''.$this->weekDayNames['full'][$day].'\'';
				$tr['eee']    = '\''.$this->weekDayNames['medium'][$day].'\'';
			}
		}

		$amPmName = $timestamp->format('a').'Name';

		if ($this->$amPmName !== null)
		{
			$tr['a'] = '\''.$this->$amPmName.'\'';
		}

		if ($tr)
		{
			$format = strtr($format, $tr);
		}

		$format = FormatConverter::convertDateIcuToPhp($format, $type, $this->locale);
		return $timestamp->format($format);
	}
}
