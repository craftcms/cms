<?php
namespace Blocks;

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
		$language = blx()->language;
		$languageData = blx()->i18n->getLanguageData($language);
		$decimalSymbol = $languageData->getNumberSymbol('decimal');
		$groupSymbol = $languageData->getNumberSymbol('group');

		$number = str_replace($groupSymbol, '', $number);
		$number = str_replace($decimalSymbol, '.', $number);

		return $number;
	}
}
