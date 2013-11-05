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
	 * @param string $language the target language
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

			// Let's see if this is a two-parter.
			$parts = explode('_', $language);
			$languageCode = false;
			if (count($parts) > 0)
			{
				$languageCode = $parts[0];
			}

			// Check the craft/app/translations for the original language first.
			$paths[] = craft()->path->getCpTranslationsPath().$language.'.php';
			if ($languageCode)
			{
				// Check the craft/app/translations folder for the language code fallback.
				$paths[] = craft()->path->getCpTranslationsPath().$languageCode.'.php';
			}

			// Check the craft/translations folder for the original language.
			$paths[] = craft()->path->getSiteTranslationsPath().$language.'.php';

			if ($languageCode)
			{
				// Check the craft/translations folder for the language code fallback.
				$paths[] = craft()->path->getSiteTranslationsPath().$languageCode.'.php';
			}

			// Only add in plugins if we're installed and not in maintenance mode (during an update).
			if (craft()->isInstalled() && !craft()->isInMaintenanceMode())
			{
				// Let's see if plugins have anything to contribute. Don't use PluginService, but go straight to the file system. Who cares if they are disabled.
				$pluginPaths = IOHelper::getFolders(craft()->path->getPluginsPath());

				if ($pluginPaths)
				{
					foreach ($pluginPaths as $pluginPath)
					{
						$paths[] = $pluginPath.'translations/'.$language.'.php';
						if ($languageCode)
						{
							$paths[] = $pluginPath.'translations/'.$languageCode.'.php';
						}
					}
				}
			}

			// Now loop through all fo the paths and see if any of these files exists.
			foreach ($paths as $filePath)
			{
				if (IOHelper::fileExists($filePath))
				{
					// Load it up.
					$translations = include($filePath);

					if (is_array($translations))
					{
						$this->_translations[$language] = array_merge($this->_translations[$language], $translations);
					}
				}
			}
		}

		return $this->_translations[$language];
	}
}
