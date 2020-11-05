<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use voku\helper\ASCII;

/**
 * The entire purpose of this class is so we can get at the charsArray in Stringy, which is a protected method
 * and the creators did not want to expose as public.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.5.0
 */
class Stringy extends \Stringy\Stringy
{
    /**
     * Public wrapper for [[langSpecificCharsArray()]].
     *
     * @param string $language Language of the source string
     * @return array An array of replacements
     * @since 3.0.10
     */
    public static function getLangSpecificCharsArray(string $language = 'en'): array
    {
        $array = ASCII::charsArrayWithOneLanguage($language);
        return [
            $array['orig'],
            $array['replace'],
        ];
    }

    /**
     * Public wrapper for [[charsArray()]].
     *
     * @return array
     */
    public function getAsciiCharMap(): array
    {
        return $this->charsArray();
    }

    /**
     * Returns the replacements for the toAscii() method, including any custom
     * mappings provided by the <config3:customAsciiCharMappings> config setting.
     *
     * @return array
     */
    protected function charsArray(): array
    {
        static $charsArray;
        return $charsArray ?? $charsArray = array_merge(
                ASCII::charsArrayWithMultiLanguageValues(),
                Craft::$app->getConfig()->getGeneral()->customAsciiCharMappings
            );
    }
}
