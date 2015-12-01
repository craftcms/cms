<?php
namespace Craft;

/**
 * Localization functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class LocalizationVariable
{
	// Public Methods
	// =========================================================================

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
	 * Returns the current locale.
	 *
	 * @return LocaleModel
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
	 * @return array
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
