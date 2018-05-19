<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\i18n\Locale;
use yii\base\InvalidArgumentException;
use yii\i18n\MissingTranslationEvent;

/**
 * Class Localization
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Localization
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private static $_translations;

    // Public Methods
    // =========================================================================

    /**
     * Normalizes a language into the correct format (e.g. `en-US`).
     *
     * @param string $language
     * @return string
     * @throws InvalidArgumentException if $language is invalid.
     */
    public static function normalizeLanguage(string $language): string
    {
        $language = strtolower(str_replace('_', '-', $language));

        $allLanguages = Craft::$app->getI18n()->getAllLocaleIds();
        $lcLanguages = array_map('strtolower', $allLanguages);
        $allLanguages = array_combine($lcLanguages, $allLanguages);

        if (!isset($allLanguages[$language])) {
            throw new InvalidArgumentException('Invalid language: '.$language);
        }

        return $allLanguages[$language];
    }

    /**
     * Normalizes a user-submitted number for use in code and/or to be saved into the database.
     * Group symbols are removed (e.g. 1,000,000 => 1000000), and decimals are converted to a periods, if the current
     * locale uses something else.
     *
     * @param mixed $number The number that should be normalized.
     * @param string|null $localeId The locale ID that the number is set in
     * @return mixed The normalized number.
     */
    public static function normalizeNumber($number, string $localeId = null)
    {
        if (is_string($number)) {
            if ($localeId !== null && $localeId !== Craft::$app->language) {
                $locale = Craft::$app->getI18n()->getLocaleById($localeId);
            } else {
                $locale = Craft::$app->getLocale();
            }

            $decimalSymbol = $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
            $groupSymbol = $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR);

            // Remove any group symbols and use a period for the decimal symbol
            $number = str_replace([$groupSymbol, $decimalSymbol], ['', '.'], $number);
        }

        return $number;
    }

    /**
     * Returns fallback data for a locale if the Intl extension isn't loaded.
     *
     * @param string $localeId
     * @return array|null
     */
    public static function localeData(string $localeId)
    {
        $data = null;

        // Load the locale data
        $appDataPath = Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'locales'.DIRECTORY_SEPARATOR.$localeId.'.php';
        $customDataPath = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.'locales'.DIRECTORY_SEPARATOR.$localeId.'.php';

        if (is_file($appDataPath)) {
            $data = require $appDataPath;
        }

        if (is_file($customDataPath)) {
            if ($data !== null) {
                $data = ArrayHelper::merge($data, require $customDataPath);
            } else {
                $data = require $customDataPath;
            }
        }

        return $data;
    }

    /**
     * Looks for a missing translation string in Yii's core translations.
     *
     * @param MissingTranslationEvent $event
     */
    public static function findMissingTranslation(MissingTranslationEvent $event)
    {
        // Look for translation file from most to least specific.  So nl_nl.php gets checked before nl.php, for example.
        $translationFiles = [];
        $parts = explode('_', $event->language);
        $totalParts = count($parts);
        $loadedAlready = false;

        for ($i = 1; $i <= $totalParts; $i++) {
            $translationFiles[] = implode('_', array_slice($parts, 0, $i));
        }

        $translationFiles = array_reverse($translationFiles);

        // First see if we have any cached info.
        foreach ($translationFiles as $translationFile) {
            $loadedAlready = false;

            // We've loaded the translation file already, just check for the translation.
            if (isset(self::$_translations[$translationFile])) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $loadedAlready = true;

                if (isset(self::$_translations[$translationFile][$event->message])) {
                    // Found a match... grab it and go.
                    $event->message = self::$_translations[$translationFile][$event->message];

                    return;
                }

                // No translation... just give up.
                return;
            }
        }

        // We've checked through an already loaded message file and there was no match. Just give up.
        if ($loadedAlready) {
            return;
        }

        // No luck in cache, check the file system.
        $frameworkMessagePath = FileHelper::normalizePath(Craft::getAlias('@app/framework/messages'));

        foreach ($translationFiles as $translationFile) {
            $path = $frameworkMessagePath.DIRECTORY_SEPARATOR.$translationFile.DIRECTORY_SEPARATOR.'yii.php';

            if (is_file($path)) {
                // Load it up.
                self::$_translations[$translationFile] = include $path;

                if (isset(self::$_translations[$translationFile][$event->message])) {
                    $event->message = self::$_translations[$translationFile][$event->message];

                    return;
                }
            } else {
                self::$_translations[$translationFile] = [];
            }
        }
    }
}
