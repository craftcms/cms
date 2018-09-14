<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;
use craft\helpers\FileHelper;
use ResourceBundle;
use yii\base\Exception;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class I18N extends \yii\i18n\I18N
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded.
     */
    private $_intlLoaded = false;

    /**
     * @var array|null All of the known locales
     * @see getAllLocales()
     */
    private $_allLocaleIds;

    /**
     * @var string[]
     * @see getAppLocaleIds()
     */
    private $_appLocaleIds;

    /**
     * @var Locale[]
     * @see getAppLocales()
     */
    private $_appLocales;

    /**
     * @var bool|null Whether [[translate()]] should wrap translations with `@` characters
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
     * @return bool Whether the Intl extension is loaded.
     */
    public function getIsIntlLoaded(): bool
    {
        return $this->_intlLoaded;
    }

    /**
     * Returns a locale by its ID.
     *
     * @param string $localeId
     * @return Locale
     */
    public function getLocaleById(string $localeId): Locale
    {
        return new Locale($localeId);
    }

    /**
     * Returns an array of all known locale IDs.
     *
     * If the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded, then this will be based on
     * all of the locale IDs it knows about. Otherwise, it will be based on the locale data files located in
     * `vendor/craftcms/cms/src/config/locales/` and `config/locales/`.
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
                $appLocalesPath = Craft::$app->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'locales';
                $customLocalesPath = Craft::$app->getPath()->getConfigPath() . '/locales';

                $localeFiles = FileHelper::findFiles($appLocalesPath, [
                    'only' => ['*.php'],
                    'recursive' => false
                ]);

                if (is_dir($customLocalesPath)) {
                    $localeFiles = array_merge($localeFiles, FileHelper::findFiles($customLocalesPath, [
                        'only' => ['*.php'],
                        'recursive' => false
                    ]));
                }

                $this->_allLocaleIds = [];

                foreach ($localeFiles as $file) {
                    $this->_allLocaleIds[] = pathinfo($file, PATHINFO_FILENAME);
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
    public function getAllLocales(): array
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
     * in `vendor/craftcms/cms/src/translations/`.
     *
     * @return Locale[] An array of [[Locale]] objects.
     * @throws Exception in case of failure
     */
    public function getAppLocales(): array
    {
        if ($this->_appLocales !== null) {
            return $this->_appLocales;
        }

        $this->_appLocales = [];

        foreach ($this->getAppLocaleIds() as $localeId) {
            $this->_appLocales[] = new Locale($localeId);
        }

        return $this->_appLocales;
    }

    /**
     * Returns an array of the locale IDs which Craft has been translated into. The list of locales is based on whatever
     * files exist in `vendor/craftcms/cms/src/translations/`.
     *
     * @return array An array of locale IDs.
     * @throws Exception in case of failure
     */
    public function getAppLocaleIds(): array
    {
        if ($this->_appLocaleIds !== null) {
            return $this->_appLocaleIds;
        }

        $localeIds = [
            Craft::$app->sourceLanguage => true,
        ];

        // Scan the translations/ dir for the others
        $dir = Craft::$app->getPath()->getCpTranslationsPath();
        $handle = opendir($dir);
        if ($handle === false) {
            throw new Exception("Unable to open directory: $dir");
        }
        while (($subDir = readdir($handle)) !== false) {
            if ($subDir !== '.' && $subDir !== '..' && is_dir($dir . DIRECTORY_SEPARATOR . $subDir)) {
                $localeIds[$subDir] = true;
            }
        }
        closedir($handle);

        // Add in any extra locales defined by the config
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!empty($generalConfig->extraAppLocales)) {
            foreach ($generalConfig->extraAppLocales as $localeId) {
                $localeIds[$localeId] = true;
            }
        }
        if ($generalConfig->defaultCpLanguage) {
            $localeIds[$generalConfig->defaultCpLanguage] = true;
        }

        return $this->_appLocaleIds = array_keys($localeIds);
    }

    // Site Locales
    // -------------------------------------------------------------------------

    /**
     * Returns an array of the site locales.
     *
     * @return Locale[] An array of [[Locale]] objects.
     */
    public function getSiteLocales(): array
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
    public function getPrimarySiteLocale(): Locale
    {
        $site = Craft::$app->getSites()->getPrimarySite();
        return new Locale($site->language);
    }

    /**
     * Returns the site's primary locale ID. The primary locale is whatever is listed first in Settings > Locales in the
     * control panel.
     *
     * @return string The primary locale ID.
     */
    public function getPrimarySiteLocaleId(): string
    {
        return Craft::$app->getSites()->getPrimarySite()->language;
    }

    /**
     * Returns an array of the site locale IDs.
     *
     * @return array An array of locale IDs.
     */
    public function getSiteLocaleIds(): array
    {
        $localeIds = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            // Make sure it's unique
            if (!in_array($site->language, $localeIds, true)) {
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
    public function getEditableLocales(): array
    {
        if (Craft::$app->getIsMultiSite()) {
            $locales = $this->getSiteLocales();
            $editableLocales = [];

            foreach ($locales as $locale) {
                if (Craft::$app->getUser()->checkPermission('editLocale:' . $locale->id)) {
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
    public function getEditableLocaleIds(): array
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

            $translation = $char . $translation . $char;
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
            $this->_translationDebugOutput = (bool)Craft::$app->getConfig()->getGeneral()->translationDebugOutput;
        }

        return $this->_translationDebugOutput;
    }
}
