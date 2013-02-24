<?php
namespace Craft;

/**
 *
 */
class LocalizationHelper
{
	/**
	 * Will take a decimal value, will remove the locale specific grouping separator and change the locale specific
	 * decimal so a dot.
	 *
	 * @static
	 * @param $number
	 * @return mixed
	 */
	public static function normalizeNumber($number)
	{
		$language = craft()->language;
		$languageData = craft()->i18n->getLocaleData($language);
		$decimalSymbol = $languageData->getNumberSymbol('decimal');
		$groupSymbol = $languageData->getNumberSymbol('group');

		$number = str_replace($groupSymbol, '', $number);
		$number = str_replace($decimalSymbol, '.', $number);

		return $number;
	}
}
