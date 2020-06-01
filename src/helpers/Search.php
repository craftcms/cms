<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;

/**
 * Search helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Search
{
    /**
     * Normalizes search keywords.
     *
     * @param string[]|string $str The dirty keywords
     * @param array $ignore Ignore words to strip out
     * @param bool $processCharMap Whether to remove punctuation and diacritics (default is true)
     * @param string|null The language that the character map should be based on, if `$processCharMap` is `true`.
     * @return string The cleansed keywords.
     */
    public static function normalizeKeywords($str, array $ignore = [], bool $processCharMap = true, string $language = null): string
    {
        // Flatten
        if (is_array($str)) {
            $str = StringHelper::toString($str, ' ');
        }

        // Get rid of tags
        $str = preg_replace('/<br\s*\/?>/i', ' ', $str);
        $str = preg_replace('/<\/\w+>/', ' $1', $str);
        $str = strip_tags($str);

        // Convert non-breaking spaces entities to regular ones
        $str = str_replace(['&nbsp;', '&#160;', '&#xa0;'], ' ', $str);

        // Get rid of entities
        $str = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $str);

        // Normalize to lowercase
        $str = mb_strtolower($str);

        if ($processCharMap) {
            // Remove punctuation and diacritics
            $punctuation = self::_getPunctuation();
            $str = str_replace(array_keys($punctuation), $punctuation, $str);
            $str = strtr($str, StringHelper::asciiCharMap(true, $language ?? Craft::$app->language));
        }

        // Remove ignore-words?
        if (is_array($ignore) && !empty($ignore)) {
            foreach ($ignore as $word) {
                $word = preg_quote(static::normalizeKeywords($word, [], true, $language), '/');
                $str = preg_replace("/\b{$word}\b/u", '', $str);
            }
        }

        // Strip out new lines and superfluous spaces
        $str = preg_replace('/[\n\r]+/u', ' ', $str);
        $str = preg_replace('/\s{2,}/u', ' ', $str);

        // Trim white space
        $str = trim($str);

        return $str;
    }

    /**
     * Returns the asciiPunctuation array.
     *
     * @return array
     */
    private static function _getPunctuation(): array
    {
        // Keep local copy
        static $asciiPunctuation = [];

        if (empty($asciiPunctuation)) {
            $asciiPunctuation = [
                '!' => ' ',
                '"' => ' ',
                '#' => ' ',
                '&' => ' ',
                "'" => '',
                '(' => ' ',
                ')' => ' ',
                '*' => ' ',
                '+' => ' ',
                ',' => ' ',
                '-' => ' ',
                '.' => ' ',
                '/' => ' ',
                ':' => ' ',
                ';' => ' ',
                '<' => ' ',
                '>' => ' ',
                '?' => ' ',
                '@' => ' ',
                '[' => ' ',
                '\\' => ' ',
                ']' => ' ',
                '^' => ' ',
                '{' => ' ',
                '|' => ' ',
                '}' => ' ',
                '~' => ' ',
                '¡' => ' ',
                '¢' => ' ',
                '£' => ' ',
                '¤' => ' ',
                '¥' => ' ',
                '¦' => ' ',
                '§' => ' ',
                '¨' => ' ',
                '©' => ' ',
                'ª' => ' ',
                '«' => ' ',
                '¬' => ' ',
                '®' => ' ',
                '¯' => ' ',
                '°' => ' ',
                '±' => ' ',
                '²' => ' ',
                '³' => ' ',
                '´' => ' ',
                'µ' => ' ',
                '¶' => ' ',
                '·' => ' ',
                '¸' => ' ',
                '¹' => ' ',
                'º' => ' ',
                '»' => ' ',
                '¼' => ' ',
                '½' => ' ',
                '¾' => ' ',
                '¿' => ' ',
                '×' => ' ',
                'ƒ' => ' ',
                'ˆ' => ' ',
                '˜' => ' ',
                '–' => ' ',
                '—' => ' ',
                '―' => ' ',
                '_' => ' ',
                '‘' => '',
                '’' => '',
                '‚' => ' ',
                '“' => ' ',
                '”' => ' ',
                '„' => ' ',
                '†' => ' ',
                '‡' => ' ',
                '•' => ' ',
                '‣' => ' ',
                '…' => ' ',
                '‰' => ' ',
                '′' => ' ',
                '″' => ' ',
                '‹' => ' ',
                '›' => ' ',
                '‼' => ' ',
                '‾' => ' ',
                '⁄' => ' ',
                '€' => ' ',
                '™' => ' ',
                '←' => ' ',
                '↑' => ' ',
                '→' => ' ',
                '↓' => ' ',
                '↔' => ' ',
                '↵' => ' ',
                '⇐' => ' ',
                '⇑' => ' ',
                '⇒' => ' ',
                '⇓' => ' ',
                '⇔' => ' ',
                '∀' => ' ',
                '∂' => ' ',
                '∃' => ' ',
                '∅' => ' ',
                '∇' => ' ',
                '∈' => ' ',
                '∉' => ' ',
                '∋' => ' ',
                '∏' => ' ',
                '∑' => ' ',
                '−' => ' ',
                '∗' => ' ',
                '√' => ' ',
                '∝' => ' ',
                '∞' => ' ',
                '∠' => ' ',
                '∧' => ' ',
                '∨' => ' ',
                '∩' => ' ',
                '∪' => ' ',
                '∫' => ' ',
                '∴' => ' ',
                '∼' => ' ',
                '≅' => ' ',
                '≈' => ' ',
                '≠' => ' ',
                '≡' => ' ',
                '≤' => ' ',
                '≥' => ' ',
                '⊂' => ' ',
                '⊃' => ' ',
                '⊄' => ' ',
                '⊆' => ' ',
                '⊇' => ' ',
                '⊕' => ' ',
                '⊗' => ' ',
                '⊥' => ' ',
                '⋅' => ' ',
                '⌈' => ' ',
                '⌉' => ' ',
                '⌊' => ' ',
                '⌋' => ' ',
                '〈' => ' ',
                '〉' => ' ',
                '◊' => ' ',
                '♠' => ' ',
                '♣' => ' ',
                '♥' => ' ',
                '♦' => ' ',
            ];
        }

        return $asciiPunctuation;
    }
}
