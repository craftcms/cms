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
	public function getAllLocales()
	{
		return blx()->i18n->getAllLocales();
	}

	/**
	 * Returns the locales that the application is translated for.
	 *
	 * @return array
	 */
	public function getAppLocales()
	{
		return blx()->i18n->getAppLocales();
	}

	/**
	 * Returns a locale by its ID.
	 *
	 * @param string $localeId
	 * @return LocaleModel
	 */
	public function getLocaleById($localeId)
	{
		return blx()->i18n->getLocaleById($localeId);
	}

	/**
	 * Returns the locales that the site is translated for.
	 *
	 * @return array
	 */
	public function getSiteLocales()
	{
		return blx()->i18n->getSiteLocales();
	}

	/**
	 * Returns the site's primary locale.
	 *
	 * @return string
	 */
	public function getPrimarySiteLocale()
	{
		return blx()->i18n->getPrimarySiteLocale();
	}
}
