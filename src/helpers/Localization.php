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
 * @since 3.0.0
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
     * @throws InvalidArgumentException if $language is invalid.
     */
    public static function normalizeLanguage(string $language): string
    {
        $language = strtolower(str_replace('_', '-', $language));

        $allLanguages = Craft::$app->getI18n()->getAllLocaleIds();
        $lcLanguages = array_map('strtolower', $allLanguages);
        $allLanguages = array_combine($lcLanguages, $allLanguages);

        if (!isset($allLanguages[$language])) {
            throw new InvalidArgumentException('Invalid language: ' . $language);
        }

        return $allLanguages[$language];
    }

    /**
     * Normalizes a user-submitted number for use in code and/or to be saved into the database.
     *
     * Group symbols are removed (e.g. 1,000,000 => 1000000), and the locale’s decimal symbol, minus sign, and digits are
     * converted to a period, a hyphen, and ASCII digits, such as `−1 234,50` in `sv-SE` => `-1234.50`. Direction marks
     * are removed.
     *
     * @param mixed $number The number that should be normalized.
     * @param string|null $localeId The locale ID that the number is set in
     * @return mixed The normalized number.
     */
    public static function normalizeNumber(mixed $number, ?string $localeId = null): mixed
    {
        if (is_string($number)) {
            if ($localeId === null) {
                $locale = Craft::$app->getFormattingLocale();
            } elseif ($localeId === Craft::$app->language) {
                $locale = Craft::$app->getLocale();
            } else {
                $locale = Craft::$app->getI18n()->getLocaleById($localeId);
            }

            $replacements = [
                "\u{061C}" => '',
                "\u{200E}" => '',
                "\u{200F}" => '',
                $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR) => '',
                $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR) => '.',
                $locale->getNumberSymbol(Locale::SYMBOL_MINUS_SIGN) => '-',
            ];

            // Locales such as ar-EG write their own digits, which follow their zero in Unicode.
            $zero = mb_ord($locale->getNumberSymbol(Locale::SYMBOL_ZERO_DIGIT));
            foreach (range(0, 9) as $digit) {
                $replacements[mb_chr($zero + $digit)] = (string)$digit;
            }

            // strtr() replaces the longest matches first, so a minus sign that includes a direction mark wins over the mark.
            $number = strtr($number, $replacements);
        }

        return $number;
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
        $frameworkMessagePath = FileHelper::normalizePath(Craft::getAlias('@app/framework/messages'));

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
