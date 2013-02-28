<?php
namespace Craft;

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
		return craft()->i18n->getAllLocales();
	}

	/**
	 * Returns the locales that the application is translated for.
	 *
	 * @return array
	 */
	public function getAppLocales()
	{
		return craft()->i18n->getAppLocales();
	}

	/**
	 * Returns a locale by its ID.
	 *
	 * @param string $localeId
	 * @return LocaleModel
	 */
	public function getLocaleById($localeId)
	{
		return craft()->i18n->getLocaleById($localeId);
	}

	/**
	 * Returns the locales that the site is translated for.
	 *
	 * @return array
	 */
	public function getSiteLocales()
	{
		return craft()->i18n->getSiteLocales();
	}

	/**
	 * Returns the site's primary locale.
	 *
	 * @return string
	 */
	public function getPrimarySiteLocale()
	{
		return craft()->i18n->getPrimarySiteLocale();
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param $localeId
	 * @return \CLocale|null
	 */
	public function getLocaleData($localeId = null)
	{
		return craft()->i18n->getLocaleData($localeId);
	}
}
