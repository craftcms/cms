<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\i18n\Locale;
use yii\i18n\MissingTranslationEvent;

/**
 * Class Localization
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * Normalizes a user-submitted number for use in code and/or to be saved into the database.
     *
     * Group symbols are removed (e.g. 1,000,000 => 1000000), and decimals are converted to a periods, if the current
     * locale uses something else.
     *
     * @param mixed $number The number that should be normalized.
     *
     * @return mixed The normalized number.
     */
    public static function normalizeNumber($number)
    {
        if (is_string($number)) {
            $locale = Craft::$app->getLocale();
            $decimalSymbol = $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
            $groupSymbol = $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR);

            // Remove any group symbols
            $number = str_replace($groupSymbol, '', $number);

            // Use a period for the decimal symbol
            $number = str_replace($decimalSymbol, '.', $number);
        }

        return $number;
    }

    /**
     * Returns fallback data for a locale if the Intl extension isn't loaded.
     *
     * @param string $localeId
     *
     * @return array|null
     */
    public static function getLocaleData($localeId)
    {
        $data = null;

        // Load the locale data
        $appDataPath = Craft::$app->getPath()->getAppPath().'/config/locales/'.$localeId.'.php';
        $customDataPath = Craft::$app->getPath()->getConfigPath().'/locales/'.$localeId.'.php';

        if (Io::fileExists($appDataPath)) {
            $data = require($appDataPath);
        }

        if (Io::fileExists($customDataPath)) {
            if ($data !== null) {
                $data = ArrayHelper::merge($data, require($customDataPath));
            } else {
                $data = require($customDataPath);
            }
        }

        return $data;
    }

    /**
     * Looks for a missing translation string in Yii's core translations.
     *
     * @param MissingTranslationEvent $event
     *
     * @return void
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
            if (isset(static::$_translations[$translationFile])) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $loadedAlready = true;

                if (isset(static::$_translations[$translationFile][$event->message])) {
                    // Found a match... grab it and go.
                    $event->message = static::$_translations[$translationFile][$event->message];

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
        $frameworkMessagePath = Io::normalizePathSeparators(Craft::getAlias('@app/framework/messages'));

        foreach ($translationFiles as $translationFile) {
            $path = $frameworkMessagePath.$translationFile.'/yii.php';

            if (Io::fileExists($path)) {
                // Load it up.
                static::$_translations[$translationFile] = include($path);

                if (isset(static::$_translations[$translationFile][$event->message])) {
                    $event->message = static::$_translations[$translationFile][$event->message];

                    return;
                }
            } else {
                static::$_translations[$translationFile] = [];
            }
        }
    }
}
