<?php
namespace Blocks;

/**
 *
 */
class LocalizationService extends \CApplicationComponent
{
	private $_translatedLanguages;

	/**
	 * Returns a list of language ids from the languages directory that @@@productDisplay@@@ is translated into.
	 * @return mixed
	 */
	public function getAppTranslatedLanguages()
	{
		if (!$this->_translatedLanguages)
		{
			$this->_translatedLanguages = array();

			$path = blx()->path->getTranslationsPath();
			$dirs = glob($path.'*.php');

			if (is_array($dirs) && count($dirs) > 0)
			{
				foreach ($dirs as $dir)
				{
					$this->_translatedLanguages[] = pathinfo($dir, PATHINFO_FILENAME);
				}
			}

			if (!in_array('en_us', $this->_translatedLanguages))
					$this->_translatedLanguages[] = 'en_us';

			sort($this->_translatedLanguages);
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
