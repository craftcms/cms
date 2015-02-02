<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use NumberFormatter;

/**
 * @inheritDoc \yii\i18n\Formatter
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
     * @var array The locale's currency symbols.
     */
	public $currencySymbols;

	/**
	 * @var boolean Whether the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 */
	private $_intlLoaded = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->_intlLoaded = extension_loaded('intl');
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
	public function asDate($value, $format = null)
	{
		if ($format === null)
		{
			$format = $this->dateFormat;
		}

		if (isset($this->dateTimeFormats[$format]['date']))
		{
			$format = 'php:'.$this->dateTimeFormats[$format]['date'];
		}

		return parent::asDate($value, $format);
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
			$format = 'php:'.$this->dateTimeFormats[$format]['date'];
		}

		return parent::asTime($value, $format);
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
			$format = 'php:'.$this->dateTimeFormats[$format]['date'];
		}

		return parent::asDatetime($value, $format);
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

		if ($this->_intlLoaded)
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
}
