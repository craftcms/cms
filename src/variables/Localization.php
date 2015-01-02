<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\i18n\Locale;

/**
 * Localization functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Localization
{
	// Public Methods
	// =========================================================================

	/**
	 * Gets all known languages.
	 *
	 * @return Locale[]
	 */
	public function getAllLocales()
	{
		return craft()->i18n->getAllLocales();
	}

	/**
	 * Returns the locales that the application is translated for.
	 *
	 * @return Locale[]
	 */
	public function getAppLocales()
	{
		return craft()->i18n->getAppLocales();
	}

	/**
	 * Returns the current locale.
	 *
	 * @return Locale
	 */
	public function getCurrentLocale()
	{
		return craft()->i18n->getLocaleById(craft()->language);
	}

	/**
	 * Returns a locale by its ID.
	 *
	 * @param string $localeId
	 *
	 * @return Locale
	 */
	public function getLocaleById($localeId)
	{
		return craft()->i18n->getLocaleById($localeId);
	}

	/**
	 * Returns the locales that the site is translated for.
	 *
	 * @return Locale[]
	 */
	public function getSiteLocales()
	{
		return craft()->i18n->getSiteLocales();
	}

	/**
	 * Returns an array of the site locale IDs.
	 *
	 * @return array
	 */
	public function getSiteLocaleIds()
	{
		return craft()->i18n->getSiteLocaleIds();
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
	 * Returns a list of locales that are editable by the current user.
	 *
	 * @return Locale[]
	 */
	public function getEditableLocales()
	{
		return craft()->i18n->getEditableLocales();
	}

	/**
	 * Returns an array of the editable locale IDs.
	 *
	 * @return array
	 */
	public function getEditableLocaleIds()
	{
		return craft()->i18n->getEditableLocaleIds();
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param string|null $localeId
	 *
	 * @return LocaleData|null
	 */
	public function getLocaleData($localeId = null)
	{
		return craft()->i18n->getLocaleData($localeId);
	}

	/**
	 * Returns the jQuery UI Datepicker date format, per the current locale.
	 *
	 * @return string
	 */
	public function getDatepickerJsFormat()
	{
		$localeData = craft()->i18n->getLocaleData(craft()->language);
		$dateFormatter = $localeData->getDateFormatter();
		return $dateFormatter->getDatepickerJsFormat();
	}

	/**
	 * Returns the jQuery Timepicker time format, per the current locale.
	 *
	 * @return string
	 */
	public function getTimepickerJsFormat()
	{
		$localeData = craft()->i18n->getLocaleData(craft()->language);
		$dateFormatter = $localeData->getDateFormatter();
		return $dateFormatter->getTimepickerPhpFormat();
	}
}
