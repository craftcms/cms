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
								$this->_translations[$language] = array_merge($this->_translations[$language], $translations);
							}
						}
					}
				}
			}
		}

		return $this->_translations[$language];
	}
}
