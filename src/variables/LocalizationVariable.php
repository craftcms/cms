<?php
namespace Blocks;

/**
 * Localization functions
 */
class LocalizationVariable
{
	/**
	 * Returns the languages that Blocks is translated into.
	 * @return mixed
	 */
	public function languages()
	{
		return blx()->localization->getAppTranslatedLanguages();
	}

	/**
	 * @return string
	 */
	public function alllanguagesanddisplaynames()
	{
		$languages = $this->languages();
		// The app is written in English, so we add it manually.
		$languageData = 'en_us:English (US),';

		// Get the language the user is currently using.
		$userLangage = blx()->localization->getLanguageData(blx()->language);
		foreach ($languages as $language)
			$languageData .= $language.':'.$userLangage->getLocaleDisplayName($language).',';

		return rtrim($languageData, ',');
	}
}
