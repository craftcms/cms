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
}
