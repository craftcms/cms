<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;
use ResourceBundle;
use yii\base\Exception;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class I18N extends \yii\i18n\I18N
{
    /**
     * @var array|null All of the known locales
     * @see getAllLocales()
     */
    private ?array $_allLocaleIds = null;

    /**
     * @var bool[]
     * @see getAppLocaleIds()
     */
    private array $_appLocaleIds;

    /**
     * @var Locale[]
     * @see getAppLocales()
     */
    private array $_appLocales;

    /**
     * @var bool|null Whether [[translate()]] should wrap translations with `@` characters
     */
    private ?bool $_translationDebugOutput = null;

    /**
     * Returns whether the [Intl extension](https://php.net/manual/en/book.intl.php) is loaded.
     *
     * @return bool Whether the Intl extension is loaded.
     * @deprecated in 4.0.0. The Intl extension is now required.
     */
    public function getIsIntlLoaded(): bool
    {
        return true;
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
     * Returns an array of all known locale IDs, according to the Intl extension.
     *
     * @return array An array of locale IDs.
     * @link https://php.net/manual/en/resourcebundle.locales.php
     */
    public function getAllLocaleIds(): array
    {
        if (!isset($this->_allLocaleIds)) {
            $this->_allLocaleIds = ResourceBundle::getLocales('');

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
        if (isset($this->_appLocales)) {
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
        $this->_defineAppLocales();
        return array_keys($this->_appLocaleIds);
    }

    /**
     * Defines the list of supported app locale IDs.
     *
     */
    private function _defineAppLocales(): void
    {
        if (isset($this->_appLocaleIds)) {
            return;
        }

        $this->_appLocaleIds = [
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
                $this->_appLocaleIds[$subDir] = true;
            }
        }
        closedir($handle);

        // Add in any extra locales defined by the config
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!empty($generalConfig->extraAppLocales)) {
            foreach ($generalConfig->extraAppLocales as $localeId) {
                $this->_appLocaleIds[$localeId] = true;
            }
        }
        if ($generalConfig->defaultCpLanguage) {
            $this->_appLocaleIds[$generalConfig->defaultCpLanguage] = true;
        }
    }

    /**
     * Returns whether the given locale ID is a supported app locale ID.
     *
     * @param string $localeId
     * @return bool
     * @since 3.6.0
     */
    public function validateAppLocaleId(string $localeId): bool
    {
        $this->_defineAppLocales();
        return isset($this->_appLocaleIds[$localeId]);
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
    public function translate($category, $message, $params, $language): ?string
    {
        $translation = parent::translate($category, $message, $params, $language);

        // If $message is a key and came back identical to the input, translate it into the source language
        if ($translation === $message && !in_array($category, ['yii', 'site'], true)) {
            $messageSource = $this->getMessageSource($category);
            if ($messageSource->sourceLanguage !== $language) {
                $translation = parent::translate($category, $message, $params, $messageSource->sourceLanguage);
            }
        }

        if ($this->_shouldAddTranslationDebugOutput()) {
            $char = match ($category) {
                'site' => '$',
                'app' => '@',
                default => '%',
            };

            $translation = $char . $translation . $char;
        }

        return $translation;
    }

    /**
     * Returns whether [[translate()]] should wrap translations with `@` characters,
     * per the `translationDebugOutput` config setting.
     *
     * @return bool
     */
    private function _shouldAddTranslationDebugOutput(): bool
    {
        if (!isset($this->_translationDebugOutput)) {
            $this->_translationDebugOutput = Craft::$app->getConfig()->getGeneral()->translationDebugOutput;
        }

        return $this->_translationDebugOutput;
    }
}
