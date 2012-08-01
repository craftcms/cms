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
			$this->_translatedLanguages = array();

			$path = blx()->path->getLanguagesPath();
			$dirs = glob($path.'*', GLOB_ONLYDIR);

			if (is_array($dirs) && count($dirs) > 0)
			{
				foreach ($dirs as $dir)
				{
					$segs = explode('/', $dir);
					$this->_translatedLanguages[] = $segs[count($segs) - 1];
				}
			}
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
