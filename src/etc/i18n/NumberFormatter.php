<?php
namespace Craft;

/**
 * Number formatter class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.i18n
 * @since     1.3
 */
class NumberFormatter extends \CNumberFormatter
{
	// Public Methods
	// =========================================================================

	/**
	 * Formats a number using the decimal format defined in the locale.
	 *
	 * @param mixed $value
	 * @param bool  $withGroupSymbol
	 *
	 * @return string
	 */
	public function formatDecimal($value, $withGroupSymbol = true)
	{
		// Let's make sure the decimal format matches the number of decimal places specified in the value.
		$decimalFormat = $this->_locale->getDecimalFormat();

		// Find the starting decimal position in the format.
		for ($formatCounter = strlen($decimalFormat) - 1; $formatCounter >= 0; $formatCounter--)
		{
			if ($decimalFormat[$formatCounter] !== '#')
			{
				break;
			}
		}

		$formatCounter += 1;

		// Find the starting decimal position in the value.
		for ($valueCounter = strlen($value) - 1; $valueCounter >= 0; $valueCounter--)
		{
			if (!is_numeric($value[$valueCounter]))
			{
				break;
			}
		}

		$valueCounter += 1;

		// Calculate how many decimals we're using.
		$decimalLength = strlen($value) - $valueCounter;

		// Adjust the format for the number of decimals.
		for ($finalCounter = $formatCounter; $finalCounter <= $decimalLength + $formatCounter; $finalCounter++)
		{
			$decimalFormat[$finalCounter] = '#';
		}

		$result = $this->format($decimalFormat, $value);

		if (!$withGroupSymbol)
		{
			$result = str_replace($this->_locale->getNumberSymbol('group'), '', $result);
		}

		return $result;
	}

	/**
	 * Formats a number using the currency format defined in the locale.
	 *
	 * @param mixed  $value
	 * @param string $currency
	 * @param bool   $stripZeroCents
	 *
	 * @return string The formatted result.
	 */
	public function formatCurrency($value, $currency, $stripZeroCents = false)
	{
		$result = parent::formatCurrency($value, $currency);

		if ($stripZeroCents)
		{
			$decimal = $this->_locale->getNumberSymbol('decimal');
			$result = preg_replace('/\\'.$decimal.'0*$/', '', $result);
		}

		return $result;
	}
}
