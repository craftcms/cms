<?php
namespace Craft;

/**
 * Number formatter class
 */
class NumberFormatter extends \CNumberFormatter
{
	/**
	 * Formats a number using the decimal format defined in the locale.
	 *
	 * @param mixed $value
	 * @param bool  $withGroupSymbol
	 * @return string
	 */
	public function formatDecimal($value, $withGroupSymbol = true)
	{
		$result = parent::formatDecimal($value);

		if (!$withGroupSymbol)
		{
			$result = str_replace($this->_locale->getNumberSymbol('group'), '', $result);
		}

		return $result;
	}

	/**
	 * Formats a number using the currency format defined in the locale.
	 *
	 * @param mixed   $value
	 * @param string $currency
	 * @param bool   $stripZeroCents
	 * @return string the formatting result.
	 */
	public function formatCurrency($value, $currency, $stripZeroCents = false)
	{
		$result = parent::formatCurrency($value, $currency);

		if ($stripZeroCents)
		{
			$decimal = $this->_locale->getNumberSymbol('decimal');
			$result = preg_replace("/{$decimal}0*$/", '', $result);
		}

		return $result;
	}
}
