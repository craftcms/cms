<?php
namespace Craft;

/**
 *
 */
class LocalizationHelper
{
	private static $_translations;

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

	/**
	 * Will attempt to check Yii's translation files for more to least specific translation of framework strings.
	 *
	 * @param \CMissingTranslationEvent $event
	 */
	public static function findMissingTranslation(\CMissingTranslationEvent $event)
	{
		// Look for translation file from most to least specific.  So nl_nl.php gets checked before nl.php, for example.
		$translationFiles = array();
		$parts = explode('_', $event->language);
		$totalParts = count($parts);

		for ($i = 1; $i <= $totalParts; $i++)
		{
			$translationFiles[] = implode('_', array_slice($parts, 0, $i));
		}

		$translationFiles = array_reverse($translationFiles);

		// First see if we have any cached info.
		foreach ($translationFiles as $translationFile)
		{
			// We've loaded the translation file already, just check for the translation.
			if (isset(static::$_translations[$translationFile]))
			{
				if (isset(static::$_translations[$translationFile][$event->message]))
				{
					$event->message = static::$_translations[$translationFile][$event->message];
				}

				// No translation... just give up.
				return;
			}
		}

		// No luck in cache, check the file system.
		$frameworkMessagePath = IOHelper::normalizePathSeparators(Craft::getPathOfAlias('app.framework.messages'));

		foreach ($translationFiles as $translationFile)
		{
			$path = $frameworkMessagePath.$translationFile.'/yii.php';

			if (IOHelper::fileExists($path))
			{
				// Load it up.
				static::$_translations[$translationFile] = include($path);

				if (isset(static::$_translations[$translationFile][$event->message]))
				{
					$event->message = static::$_translations[$translationFile][$event->message];
					return;
				}
			}
		}
	}
}
