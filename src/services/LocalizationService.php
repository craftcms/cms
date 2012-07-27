<?php
namespace Blocks;

/**
 *
 */
class LocalizationService extends \CApplicationComponent
{
	private $_translatedLanguages;

	/**
	 * Returns a list of language ids from the languages directory that Blocks is translated into.
	 * @return mixed
	 */
	public function getAppTranslatedLanguages()
	{
		if (!$this->_translatedLanguages)
		{
			$path = blx()->path->getLanguagesPath();
			$dirs = glob($path.'*', GLOB_ONLYDIR);
			$languages = array();

			if (is_array($dirs) && count($dirs) > 0)
			{
				foreach ($dirs as $dir)
				{
					$segs = explode('/', $dir);
					$languages[] = $segs[count($segs) - 1];
				}

				$this->_translatedLanguages = $languages;
			}
				$this->_translatedLanguages = null;
		}

		return $this->_translatedLanguages;
	}

	/**
	 * @param $languageCode
	 *
	 * @return bool|\CLocale
	 */
	public function getLanguageData($languageCode)
	{
		if (Locale::exists($languageCode))
			return Locale::getInstance($languageCode);

		return false;
	}
}
