<?php
namespace Blocks;

/**
 * Localization functions
 */
class LocalizationVariable
{
	/**
	 * Gets all known languages.
	 *
	 * @return array
	 */
	public function getLanguages()
	{
		return blx()->i18n->getLanguages();
	}

	/**
	 * Gets the languages that Blocks is translated into.
	 *
	 * @return array
	 */
	public function getTranslatedLanguages()
	{
		return blx()->i18n->getTranslatedLanguages();
	}

	/**
	 * Gets a locale's display name in the language the user is currently using.
	 *
	 * @param string $locale   The locale to get the display name of
	 * @param string $language The language to translate the locale name into
	 * @return string The locale display name
	 */
	public function getLocaleName($locale, $language = null)
	{
		// If no language is specified, default to the user's language
		if (!$language)
			$language = blx()->language;

		$languageData = blx()->i18n->getLanguageData($language);
		$localeName = $languageData->getLocaleDisplayName($locale);

		return $localeName;
	}
}
