<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\i18n;

use Craft;
use craft\app\db\Query;
use craft\app\helpers\Io;
use ResourceBundle;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class I18N extends \yii\i18n\I18N
{
	// Properties
	// =========================================================================

	/**
	 * @var boolean Whether the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 */
	private $_intlLoaded = false;

	/**
	 * @var array All of the known locales
	 * @see getAllLocales()
	 */
	private $_allLocaleIds;

	/**
	 * @var
	 */
	private $_appLocales;

	/**
	 * @var
	 */
	private $_siteLocales;

	/**
	 * @var boolean Whether [[translate()]] should wrap translations with `@` characters
	 */
	private $_translationDebugOutput;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->_intlLoaded = extension_loaded('intl');
	}

	/**
	 * Returns whether the [Intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 *
	 * @return boolean Whether the Intl extension is loaded.
	 */
	public function getIsIntlLoaded()
	{
		return $this->_intlLoaded;
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
		return new Locale($localeId);
	}

	/**
	 * Returns an array of all known locale IDs.
	 *
	 * If the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded, then this will be based on
	 * all of the locale IDs it knows about. Otherwise, it will be based on the locale data files located in
	 * craft/app/config/locales/ and craft/config/locales/.
	 *
	 * @return array An array of locale IDs.
	 * @link http://php.net/manual/en/resourcebundle.locales.php
	 */
	public function getAllLocaleIds()
	{
		if ($this->_allLocaleIds === null) {
			if ($this->getIsIntlLoaded()) {
				$this->_allLocaleIds = ResourceBundle::getLocales(null);
			} else {
				$appLocalesPath = Craft::$app->getPath()->getAppPath().'/config/locales';
				$customLocalesPath = Craft::$app->getPath()->getConfigPath().'/locales';

				$localeFiles = Io::getFolderContents($appLocalesPath, false, '\.php$');
				$customLocaleFiles = Io::getFolderContents($customLocalesPath, false, '\.php$');

				if ($localeFiles === false) {
					$localeFiles = array();
				}

				if ($customLocaleFiles !== false) {
					$localeFiles = array_merge($localeFiles, $customLocaleFiles);
				}

				$this->_allLocaleIds = [];

				foreach ($localeFiles as $file) {
					$this->_allLocaleIds[] = Io::getFilename($file, false);
				}
			}

			// Hyphens, not underscores
			foreach ($this->_allLocaleIds as $i => $locale) {
				$this->_allLocaleIds[$i] = str_replace('_', '-', $locale);
			}
		}

		return $this->_allLocaleIds;
	}

	/**
	 * Returns an array of all known locales.
	 *
	 * @return Locale[] An array of [[Locale]] objects.
	 * @see getAllLocaleIds()
	 */
	public function getAllLocales()
	{
		$locales = [];
		$localeIds = $this->getAllLocaleIds();

		foreach ($localeIds as $localeId) {
			$locales[] = new Locale($localeId);
		}

		return $locales;
	}

	// Application Locales
	// -------------------------------------------------------------------------

	/**
	 * Returns an array of locales that Craft is translated into. The list of locales is based on whatever files exist
	 * in craft/app/translations/.
	 *
	 * @return Locale[] An array of [[Locale]] objects.
	 */
	public function getAppLocales()
	{
		if ($this->_appLocales === null) {
			$this->_appLocales = [new Locale('en-US')];

			$path = Craft::$app->getPath()->getCpTranslationsPath();
			$folders = Io::getFolderContents($path, false);

			if (is_array($folders) && count($folders) > 0) {
				foreach ($folders as $dir) {
					$localeId = Io::getFilename($dir, false);

					if ($localeId != 'en-US') {
						$this->_appLocales[] = new Locale($localeId);
					}
				}
			}
		}

		return $this->_appLocales;
	}

	/**
	 * Returns an array of the locale IDs which Craft has been translated into. The list of locales is based on whatever
	 * files exist in craft/app/translations/.
	 *
	 * @return array An array of locale IDs.
	 */
	public function getAppLocaleIds()
	{
		$locales = $this->getAppLocales();
		$localeIds = [];

		foreach ($locales as $locale) {
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	// Site Locales
	// -------------------------------------------------------------------------

	/**
	 * Returns an array of the site locales.
	 *
	 * @return Locale[] An array of [[Locale]] objects.
	 */
	public function getSiteLocales()
	{
		$locales = [];

		foreach ($this->getSiteLocaleIds() as $localeId) {
			$locales[] = new Locale($localeId);
		}

		return $locales;
	}

	/**
	 * Returns the site's primary locale. The primary locale is whatever is listed first in Settings > Locales in the
	 * control panel.
	 *
	 * @return Locale A [[Locale]] object representing the primary locale.
	 */
	public function getPrimarySiteLocale()
	{
		$locales = $this->getSiteLocales();

		return $locales[0];
	}

	/**
	 * Returns the site's primary locale ID. The primary locale is whatever is listed first in Settings > Locales in the
	 * control panel.
	 *
	 * @return string The primary locale ID.
	 */
	public function getPrimarySiteLocaleId()
	{
		return $this->getPrimarySiteLocale()->id;
	}

	/**
	 * Returns an array of the site locale IDs.
	 *
	 * @return array An array of locale IDs.
	 */
	public function getSiteLocaleIds()
	{
		$localeIds = [];

		foreach (Craft::$app->getSites()->getAllSites() as $site) {
			// Make sure it's unique
			if (!in_array($site->language, $localeIds)) {
				$localeIds[] = $site->language;
			}
		}

		return $localeIds;
	}

	/**
	 * Returns a list of locales that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableLocales()
	{
		if (Craft::$app->getIsMultiSite()) {
			$locales = $this->getSiteLocales();
			$editableLocales = [];

			foreach ($locales as $locale) {
				if (Craft::$app->getUser()->checkPermission('editLocale:'.$locale->id)) {
					$editableLocales[] = $locale;
				}
			}

			return $editableLocales;
		}

		return $this->getSiteLocales();
	}

	/**
	 * Returns an array of the editable locale IDs.
	 *
	 * @return array
	 */
	public function getEditableLocaleIds()
	{
		$locales = $this->getEditableLocales();
		$localeIds = [];

		foreach ($locales as $locale) {
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * @inheritdoc
	 */
	public function translate($category, $message, $params, $language)
	{
		$translation = parent::translate($category, $message, $params, $language);

		if ($this->_shouldAddTranslationDebugOutput()) {
			switch ($category) {
				case 'site':
					$char = '$';
					break;
				case 'app':
					$char = '@';
					break;
				default:
					$char = '%';
			}

			$translation = $char.$translation.$char;
		}

		return $translation;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns whether [[translate()]] should wrap translations with `@` characters,
	 * per the `translationDebugOutput` config setting.
	 */
	private function _shouldAddTranslationDebugOutput()
	{
		if ($this->_translationDebugOutput === null) {
			$this->_translationDebugOutput = (bool)Craft::$app->getConfig()->get('translationDebugOutput');
		}

		return $this->_translationDebugOutput;
	}
}
