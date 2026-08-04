<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use yii\i18n\MissingTranslationEvent;

/**
 * Class Localization
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see I18N} instead.
 */
class Localization
{
    /**
     * @var string[][]
     */
    private static array $_translations = [];

    /**
     * Normalizes a language into the correct format (e.g. `en-US`).
     *
     * @param string $language
     * @return string
     */
    public static function normalizeLanguage(string $language): string
    {
        return I18N::normalizeLanguage($language);
    }

    /**
     * Normalizes a user-submitted number for use in code and/or to be saved into the database.
     *
     * Group symbols are removed (e.g. 1,000,000 => 1000000), and decimals are converted to a periods, if the current
     * locale uses something else.
     *
     * @param mixed $number The number that should be normalized.
     * @param string|null $localeId The locale ID that the number is set in
     * @return mixed The normalized number.
     */
    public static function normalizeNumber(mixed $number, ?string $localeId = null): mixed
    {
        return I18N::normalizeNumber($number, $localeId);
    }

    /**
     * Looks for a missing translation string in Yii's core translations.
     *
     * @param MissingTranslationEvent $event
     */
    public static function findMissingTranslation(MissingTranslationEvent $event): void
    {
        // Look for translation file from most to least specific.  So nl_nl.php gets checked before nl.php, for example.
        $translationFiles = [];
        $parts = explode('_', $event->language);
        $totalParts = count($parts);

        for ($i = 1; $i <= $totalParts; $i++) {
            $translationFiles[] = implode('_', array_slice($parts, 0, $i));
        }

        $translationFiles = array_reverse($translationFiles);

        // First see if we have any cached info.
        foreach ($translationFiles as $translationFile) {
            // We've loaded the translation file already, just check for the translation.
            if (isset(self::$_translations[$translationFile])) {
                if (isset(self::$_translations[$translationFile][$event->message])) {
                    // Found a match... grab it and go.
                    $event->message = self::$_translations[$translationFile][$event->message];

                    return;
                }

                // No translation... just give up.
                return;
            }
        }

        // No luck in cache, check the file system.
        $frameworkMessagePath = FileHelper::normalizePath(Aliases::get('@app/framework/messages'));

        foreach ($translationFiles as $translationFile) {
            $path = $frameworkMessagePath . DIRECTORY_SEPARATOR . $translationFile . DIRECTORY_SEPARATOR . 'yii.php';

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
