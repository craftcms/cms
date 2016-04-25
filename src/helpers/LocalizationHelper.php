<?php
namespace Craft;

/**
 * Class LocalizationHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class LocalizationHelper
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private static $_translations;

	// Public Methods
	// =========================================================================

	/**
	 * Normalizes a user-submitted number for use in code and/or to be saved into the database.
	 *
	 * Group symbols are removed (e.g. 1,000,000 => 1000000), and decimals are converted to a periods, if the current
	 * locale uses something else.
	 *
	 * @param mixed $number The number that should be normalized.
	 *
	 * @return mixed The normalized number.
	 */
	public static function normalizeNumber($number)
	{
		if (is_string($number))
		{
			$language = craft()->language;
			$languageData = craft()->i18n->getLocaleData($language);
			$decimalSymbol = $languageData->getNumberSymbol('decimal');
			$groupSymbol = $languageData->getNumberSymbol('group');

			// Remove any group symbols
			$number = str_replace($groupSymbol, '', $number);

			// Use a period for the decimal symbol
			$number = str_replace($decimalSymbol, '.', $number);
		}

		return $number;
	}

	/**
	 * Looks for a missing translation string in Yii's core translations.
	 *
	 * @param \CMissingTranslationEvent $event
	 *
	 * @return null
	 */
	public static function findMissingTranslation(\CMissingTranslationEvent $event)
	{
		// Look for translation file from most to least specific.  So nl_nl.php gets checked before nl.php, for example.
		$translationFiles = array();
		$parts = explode('_', $event->language);
		$totalParts = count($parts);
		$loadedAlready = false;

		for ($i = 1; $i <= $totalParts; $i++)
		{
			$translationFiles[] = implode('_', array_slice($parts, 0, $i));
		}

		$translationFiles = array_reverse($translationFiles);

		// First see if we have any cached info.
		foreach ($translationFiles as $translationFile)
		{
			$loadedAlready = false;

			// We've loaded the translation file already, just check for the translation.
			if (isset(static::$_translations[$translationFile]))
			{
				$loadedAlready = true;

				if (isset(static::$_translations[$translationFile][$event->message]))
				{
					// Found a match... grab it and go.
					$event->message = static::$_translations[$translationFile][$event->message];
					return;
				}
			}
		}

		// We've checked through an already loaded message file and there was no match. Just give up.
		if ($loadedAlready)
		{
			return;
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
			else
			{
				static::$_translations[$translationFile] = array();
			}
		}
	}
}
