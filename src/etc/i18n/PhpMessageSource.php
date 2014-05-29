<?php
namespace Craft;

/**
 *
 */
class PhpMessageSource extends \CPhpMessageSource
{
	public $forceTranslation = true;

	private $_translations;

	/**
	 * Loads the message translation for the specified language and category.
	 *
	 * @param string $category the message category
	 * @param string $language the target locale
	 * @return array the loaded messages
	 */
	protected function loadMessages($category, $language)
	{
		if ($category != 'craft')
		{
			return parent::loadMessages($category, $language);
		}

		if (!isset($this->_translations[$language]))
		{
			$this->_translations[$language] = array();

			// Plugin translations get added first so they always lose out for conflicts
			if (craft()->isInstalled() && !craft()->isInMaintenanceMode())
			{
				// Don't use PluginService, but go straight to the file system. Who cares if they are disabled.
				$pluginPaths = IOHelper::getFolders(craft()->path->getPluginsPath());

				if ($pluginPaths)
				{
					foreach ($pluginPaths as $pluginPath)
					{
						$paths[] = $pluginPath.'translations/';
					}
				}
			}

			// Craft's translations are up next
			$paths[] = craft()->path->getCpTranslationsPath();

			// Add in Yii's i18n data, which we're going to do some special parsing on
			$paths[] = craft()->path->getFrameworkPath().'i18n/data/';

			// Site translations take the highest precidence, so they get added last
			$paths[] = craft()->path->getSiteTranslationsPath();

			// Look for translation file from least to most specific.
			// So nl.php gets loaded before nl_nl.php, for example.
			$translationFiles = array();
			$parts = explode('_', $language);
			$totalParts = count($parts);

			for ($i = 1; $i <= $totalParts; $i++)
			{
				$translationFiles[] = implode('_', array_slice($parts, 0, $i));
			}

			// Now loop through all of the paths and translation files and import the ones that exist
			foreach ($paths as $folderPath)
			{
				if (IOHelper::folderExists($folderPath))
				{
					foreach ($translationFiles as $file)
					{
						$path = $folderPath.$file.'.php';

						if (IOHelper::fileExists($path))
						{
							// Load it up.
							$translations = include($path);

							if (is_array($translations))
							{
								// If this is framework data and we're not on en_us, then do some special processing.
								if (strpos($path, 'framework/i18n/data') !== false && $file !== 'en_us')
								{
									$translations = $this->_processFrameworkData($file);
								}

								$this->_translations[$language] = array_merge($this->_translations[$language], $translations);
							}
						}
					}
				}
			}
		}

		return $this->_translations[$language];
	}

	/**
	 * @param $localeId
	 * @return array
	 */
	private function _processFrameworkData($localeId)
	{
		$wideMonthKeys = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		$abbreviatedMonthKeys = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$wideWeekdayNameKeys = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		$abbreviatedWeekdayNameKeys = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
		$amNameKey = 'AM';
		$pmNameKey = 'PM';

		$formattedFrameworkData = array();
		$locale = \CLocale::getInstance($localeId);

		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($wideMonthKeys, $locale->getMonthNames()));
		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($abbreviatedMonthKeys, $locale->getMonthNames('abbreviated')));
		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($wideWeekdayNameKeys, $locale->getWeekDayNames()));
		$formattedFrameworkData = array_merge($formattedFrameworkData, array_combine($abbreviatedWeekdayNameKeys, $locale->getWeekDayNames('abbreviated')));
		$formattedFrameworkData[$amNameKey] = $locale->getAMName();
		$formattedFrameworkData[$pmNameKey] = $locale->getPMName();

		return $formattedFrameworkData;
	}
}
