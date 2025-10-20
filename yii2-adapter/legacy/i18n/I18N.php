<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use Craft;
use craft\i18n\Locale as LegacyLocale;
use CraftCms\Cms\Support\Facades\I18N as I18NFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Translation\Locale;
use yii\base\Exception;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Translation\I18N} instead.
 */
class I18N extends \yii\i18n\I18N
{
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
     * @return LegacyLocale
     */
    public function getLocaleById(string $localeId): LegacyLocale
    {
        $locale = I18NFacade::getLocaleById($localeId);

        return LegacyLocale::fromNewLocale($locale);
    }

    /**
     * Returns an array of all known locale IDs, according to the Intl extension.
     *
     * @return array An array of locale IDs.
     * @link https://php.net/manual/en/resourcebundle.locales.php
     */
    public function getAllLocaleIds(): array
    {
        return I18NFacade::getAllLocaleIds()->all();
    }

    /**
     * Returns an array of all known locales.
     *
     * @return LegacyLocale[] An array of [[Locale]] objects.
     * @see getAllLocaleIds()
     */
    public function getAllLocales(): array
    {
        return I18NFacade::getAllLocales()
            ->map(fn(Locale $locale) => LegacyLocale::fromNewLocale($locale))
            ->all();
    }

    // Application Locales
    // -------------------------------------------------------------------------

    /**
     * Returns an array of locales that Craft is translated into. The list of locales is based on whatever files exist
     * in `vendor/craftcms/cms/src/translations/`.
     *
     * @return LegacyLocale[] An array of [[Locale]] objects.
     * @throws Exception in case of failure
     */
    public function getAppLocales(): array
    {
        return I18NFacade::getAppLocales()
            ->map(fn(Locale $locale) => LegacyLocale::fromNewLocale($locale))
            ->all();
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
        return I18NFacade::getAppLocaleIds()->all();
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
        return I18NFacade::validateAppLocaleId($localeId);
    }

    // Site Locales
    // -------------------------------------------------------------------------

    /**
     * Returns an array of the site locales.
     *
     * @return LegacyLocale[] An array of [[Locale]] objects.
     */
    public function getSiteLocales(): array
    {
        return app(\CraftCms\Cms\Translation\I18N::class)
            ->getSiteLocales()
            ->map(fn(Locale $locale) => LegacyLocale::fromNewLocale($locale))
            ->all();
    }

    /**
     * Returns the site's primary locale. The primary locale is whatever is listed first in Settings > Locales in the
     * control panel.
     *
     * @return LegacyLocale A [[Locale]] object representing the primary locale.
     * @deprecated in 5.0.0. [[\craft\models\Site::getLocale()]] should be used instead.
     */
    public function getPrimarySiteLocale(): LegacyLocale
    {
        return LegacyLocale::fromNewLocale(Sites::getPrimarySite()->getLocale());
    }

    /**
     * Returns the site's primary locale ID. The primary locale is whatever is listed first in Settings > Locales in the
     * control panel.
     *
     * @return string The primary locale ID.
     * @deprecated in 5.0.0. [[\craft\models\Site::$language]] should be used instead.
     */
    public function getPrimarySiteLocaleId(): string
    {
        return Sites::getPrimarySite()->getLanguage();
    }

    /**
     * Returns an array of the site locale IDs.
     *
     * @return array An array of locale IDs.
     */
    public function getSiteLocaleIds(): array
    {
        return I18NFacade::getSiteLocaleIds()->all();
    }

    /**
     * Returns a list of locales that are editable by the current user.
     *
     * @return array
     */
    public function getEditableLocales(): array
    {
        return app(\CraftCms\Cms\Translation\I18N::class)
            ->getEditableLocales()
            ->map(fn(Locale $locale) => LegacyLocale::fromNewLocale($locale))
            ->all();
    }

    /**
     * Returns an array of the editable locale IDs.
     *
     * @return array
     */
    public function getEditableLocaleIds(): array
    {
        return I18NFacade::getEditableLocaleIds()->all();
    }

    /**
     * @inheritdoc
     */
    public function translate($category, $message, $params, $language): ?string
    {
        return I18NFacade::translate($message, $params, $category, $language);
    }
}
