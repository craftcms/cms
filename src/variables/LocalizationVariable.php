<?php
namespace Blocks;

/**
 * Localization functions
 */
class LocalizationVariable
{
	/**
	 * Gets the current language in use.
	 *
	 * @return string
	 */
	public function getCurrentLanguage()
	{
		return blx()->language;
	}

	/**
	 * Gets the languages that @@@productDisplay@@@ is translated into.
	 *
	 * @return array
	 */
	public function languages()
	{
		return blx()->localization->getAppTranslatedLanguages();
	}

	/**
	 * Gets a locale's display name in the language the user is currently using.
	 *
	 * @param string $locale   The locale to get the display name of
	 * @param string $language The language to translate the locale name into
	 * @return string The locale display name
	 */
	public function localeName($locale, $language = null)
	{
		// If no language is specified, default to the user's language
		if (!$language)
			$language = blx()->language;

		$languageData = blx()->localization->getLanguageData($language);
		$localeName = $languageData->getLocaleDisplayName($locale);
		return $localeName;
	}
}
