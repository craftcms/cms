<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\i18n\Locale;
use yii\helpers\FormatConverter;

/**
 * Localization functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class I18N
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
		return \Craft::$app->getI18n()->getAllLocales();
	}

	/**
	 * Returns the locales that the application is translated for.
	 *
	 * @return Locale[]
	 */
	public function getAppLocales()
	{
		return \Craft::$app->getI18n()->getAppLocales();
	}

	/**
	 * Returns the current locale.
	 *
	 * @return Locale
	 */
	public function getCurrentLocale()
	{
		return \Craft::$app->getI18n()->getLocaleById(\Craft::$app->language);
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
		return \Craft::$app->getI18n()->getLocaleById($localeId);
	}

	/**
	 * Returns the locales that the site is translated for.
	 *
	 * @return Locale[]
	 */
	public function getSiteLocales()
	{
		return \Craft::$app->getI18n()->getSiteLocales();
	}

	/**
	 * Returns an array of the site locale IDs.
	 *
	 * @return array
	 */
	public function getSiteLocaleIds()
	{
		return \Craft::$app->getI18n()->getSiteLocaleIds();
	}

	/**
	 * Returns the site's primary locale.
	 *
	 * @return string
	 */
	public function getPrimarySiteLocale()
	{
		return \Craft::$app->getI18n()->getPrimarySiteLocale();
	}

	/**
	 * Returns a list of locales that are editable by the current user.
	 *
	 * @return Locale[]
	 */
	public function getEditableLocales()
	{
		return \Craft::$app->getI18n()->getEditableLocales();
	}

	/**
	 * Returns an array of the editable locale IDs.
	 *
	 * @return array
	 */
	public function getEditableLocaleIds()
	{
		return \Craft::$app->getI18n()->getEditableLocaleIds();
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param string|null $localeId
	 *
	 * @return Locale
	 */
	public function getLocaleData($localeId = null)
	{
		if ($localeId === null)
		{
			// Return the current application locale
			return \Craft::$app->getLocale();
		}
		else
		{
			return new Locale($localeId);
		}
	}

	/**
	 * Returns the jQuery UI Datepicker date format, per the current locale.
	 *
	 * @return string
	 */
	public function getDatepickerJsFormat()
	{
		$locale = \Craft::$app->getLocale();
		$format = $locale->getDateFormat(Locale::FORMAT_SHORT);
		return FormatConverter::convertDateIcuToJui($format);
	}

	/**
	 * Returns the jQuery Timepicker time format, per the current locale.
	 *
	 * @return string
	 */
	public function getTimepickerJsFormat()
	{
		$locale = \Craft::$app->getLocale();
		$format = $locale->getTimeFormat(Locale::FORMAT_SHORT);
		return FormatConverter::convertDateIcuToPhp($format);
	}

	/**
	 * Returns whether the [Intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 *
	 * @return boolean Whether the Intl extension is loaded.
	 */
	public function getIsIntlLoaded()
	{
		return \Craft::$app->getI18n()->getIsIntlLoaded();
	}
}
