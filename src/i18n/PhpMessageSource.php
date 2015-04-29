<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use craft\app\helpers\IOHelper;

/**
 * Class PhpMessageSource
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PhpMessageSource extends \yii\i18n\PhpMessageSource
{
	// Properties
	// =========================================================================

	/**
	 * Whether to force message translation when the source and target languages are the same. Yii defaults this to
	 * false, meaning translation is only performed when source and target languages are different, but Craft defaults
	 * it to true.
	 *
	 * @var boolean
	 */
	//public $forceTranslation = true;

	/**
	 * @var
	 */
	//private $_translations;

	/**
	 * @var array
	 */
	//private $_messages = [];

	// Public Methods
	// ------------------------------------------------------------------------

	/**
	 *
	 */
	//public function init()
	//{
		//$this->basePath = Craft::$app->getPath()->getFrameworkPath().'messages';
		//parent::init();
	//}

	// Protected Methods
	// =========================================================================

	/**
	 * Loads the message translation for the specified language and category.
	 *
	 * @param string $category The message category
	 * @param string $language The target locale
	 *
	 * @return array The loaded messages
	 */
	//protected function loadMessages($category, $language)
	//{
		//if ($category !== 'craft')
		//{
		//	$parentMessages = parent::loadMessages($category, $language);

			// See if there any craft/translations for Yii's system messages.
		//	if (($filePath = IOHelper::fileExists(Craft::$app->getPath()->getSiteTranslationsPath().'/'.$language.'.php')) !== false)
		//	{
		//		$parentMessages = array_merge($parentMessages, include($filePath));
		//	}
//
//			return $parentMessages;
//		}

//		if (!isset($this->_translations[$language]))
//		{
//			$this->_translations[$language] = [];

			// Plugin translations get added first so they always lose out for conflicts
//			if (Craft::$app->isInstalled() && !Craft::$app->isInMaintenanceMode())
//			{
				// Don't use PluginService, but go straight to the file system. Who cares if they are disabled.
//				$pluginPaths = IOHelper::getFolders(Craft::$app->getPath()->getPluginsPath());

//				if ($pluginPaths)
//				{
//					foreach ($pluginPaths as $pluginPath)
//					{
//						$paths[] = $pluginPath.'translations';
//					}
//				}
//			}

			// Craft's translations are up next
//			$paths[] = Craft::$app->getPath()->getCpTranslationsPath();

			// Add in Yii's i18n data, which we're going to do some special parsing on
//			$paths[] = Craft::$app->getPath()->getFrameworkPath().'i18n/data';

			// Site translations take the highest precidence, so they get added last
//			$paths[] = Craft::$app->getPath()->getSiteTranslationsPath();

			// Look for translation file from least to most specific. For example, nl.php gets loaded before nl_nl.php.
//			$translationFiles = [];
//			$parts = explode('_', $language);
//			$totalParts = count($parts);

//			for ($i = 1; $i <= $totalParts; $i++)
//			{
//				$translationFiles[] = implode('_', array_slice($parts, 0, $i));
//			}

			// Now loop through all of the paths and translation files and import the ones that exist
//			foreach ($paths as $folderPath)
//			{
//				if (IOHelper::folderExists($folderPath))
//				{
//					foreach ($translationFiles as $file)
//					{
//						$path = $folderPath.'/'.$file.'.php';

//						if (IOHelper::fileExists($path))
//						{
							// Load it up.
//							$translations = include($path);

//							if (is_array($translations))
//							{
								// If this is framework data and we're not on en_us, then do some special processing.
//								if (StringHelper::contains($path, 'framework/i18n/data') && $file !== 'en_us')
//								{
//									$translations = $this->_processFrameworkData($file);
//								}

//								$this->_translations[$language] = array_merge($this->_translations[$language], $translations);
//							}
//						}
//					}
//				}
//			}
//		}

//		return $this->_translations[$language];
	//}

	/**
     * Returns message file path for the specified language and category.
	 *
	 * @param string $category the message category
	 * @param string $language the target language
	 * @return string path to message file
	 */
	protected function getMessageFilePath($category, $language)
	{
		if ($category == 'app')
		{
			return Craft::getAlias($this->basePath)."/$language.php";
		}

		return parent::getMessageFilePath($category, $language);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $localeId
	 *
	 * @return array
	 */
	private function _processFrameworkData($localeId)
	{
		$wideMonthKeys = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
		$abbreviatedMonthKeys = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		$wideWeekdayNameKeys = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		$abbreviatedWeekdayNameKeys = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

		$formattedFrameworkData = [];
		$locale = new Locale($localeId);

		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($wideMonthKeys, $locale->getMonthNames(Locale::FORMAT_FULL)));
		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($abbreviatedMonthKeys, $locale->getMonthNames(Locale::FORMAT_MEDIUM)));
		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($wideWeekdayNameKeys, $locale->getWeekDayNames(Locale::FORMAT_FULL)));
		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($abbreviatedWeekdayNameKeys, $locale->getWeekDayNames(Locale::FORMAT_MEDIUM)));

		// Because sometimes Twig (ultimately PHP) will return 'pm' or 'am' and sometimes it will return 'PM' or 'AM'
		// and array indexes are case sensitive.
		$amName = $locale->getAMName();
		$pmName = $locale->getPMName();

		$formattedFrameworkData['AM'] = $amName;
		$formattedFrameworkData['am'] = $amName;
		$formattedFrameworkData['PM'] = $pmName;
		$formattedFrameworkData['pm'] = $pmName;

		return $formattedFrameworkData;
	}
}
