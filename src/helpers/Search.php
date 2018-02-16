<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

/**
 * Search helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Search
{
    // Public Methods
    // =========================================================================

    /**
     * Normalizes search keywords.
     *
     * @param string[]|string $str The dirty keywords
     * @param array $ignore Ignore words to strip out
     * @param bool $processCharMap Whether to remove punctuation and diacritics (default is true)
     * @return string The cleansed keywords.
     */
    public static function normalizeKeywords($str, array $ignore = [], bool $processCharMap = true): string
    {
        // Flatten
        if (is_array($str)) {
            $str = StringHelper::toString($str, ' ');
        }

        // Get rid of tags
        $str = strip_tags($str);

        // Convert non-breaking spaces entities to regular ones
        $str = str_replace(['&nbsp;', '&#160;', '&#xa0;'], ' ', $str);

        // Get rid of entities
        $str = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $str);

        // Normalize to lowercase
        $str = StringHelper::toLowerCase($str);

        if ($processCharMap) {
            // Remove punctuation and diacritics
            $str = strtr($str, self::_getCharMap());
        }

        // Remove ignore-words?
        if (is_array($ignore) && !empty($ignore)) {
            foreach ($ignore as $word) {
                $word = preg_quote(static::normalizeKeywords($word), '/');
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

    // Private Methods
    // =========================================================================

    /**
     * Get array of chars to be used for conversion.
     *
     * @return array
     */
    private static function _getCharMap(): array
    {
        // Keep local copy
        static $map = [];

        if (empty($map)) {
            // This will replace accented chars with non-accented chars
            foreach (StringHelper::asciiCharMap() as $asciiChar => $charsArray) {
                foreach ($charsArray as $char) {
                    $map[$char] = $asciiChar;
                }
            }

            // Replace punctuation with a space
            foreach (self::_getPunctuation() as $value) {
                $map[$value] = ' ';
            }
        }

        // Return the char map
        return $map;
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
                '!',
                '"',
                '#',
                '&',
                '\'',
                '(',
                ')',
                '*',
                '+',
                ',',
                '-',
                '.',
                '/',
                ':',
                ';',
                '<',
                '>',
                '?',
                '@',
                '[',
                '\\',
                ']',
                '^',
                '{',
                '|',
                '}',
                '~',
                '¡',
                '¢',
                '£',
                '¤',
                '¥',
                '¦',
                '§',
                '¨',
                '©',
                'ª',
                '«',
                '¬',
                '®',
                '¯',
                '°',
                '±',
                '²',
                '³',
                '´',
                'µ',
                '¶',
                '·',
                '¸',
                '¹',
                'º',
                '»',
                '¼',
                '½',
                '¾',
                '¿',
                '×',
                'ƒ',
                'ˆ',
                '˜',
                '–',
                '—',
                '―',
                '‘',
                '’',
                '‚',
                '“',
                '”',
                '„',
                '†',
                '‡',
                '•',
                '‣',
                '…',
                '‰',
                '′',
                '″',
                '‹',
                '›',
                '‼',
                '‾',
                '⁄',
                '€',
                '™',
                '←',
                '↑',
                '→',
                '↓',
                '↔',
                '↵',
                '⇐',
                '⇑',
                '⇒',
                '⇓',
                '⇔',
                '∀',
                '∂',
                '∃',
                '∅',
                '∇',
                '∈',
                '∉',
                '∋',
                '∏',
                '∑',
                '−',
                '∗',
                '√',
                '∝',
                '∞',
                '∠',
                '∧',
                '∨',
                '∩',
                '∪',
                '∫',
                '∴',
                '∼',
                '≅',
                '≈',
                '≠',
                '≡',
                '≤',
                '≥',
                '⊂',
                '⊃',
                '⊄',
                '⊆',
                '⊇',
                '⊕',
                '⊗',
                '⊥',
                '⋅',
                '⌈',
                '⌉',
                '⌊',
                '⌋',
                '〈',
                '〉',
                '◊',
                '♠',
                '♣',
                '♥',
                '♦'
            ];
        }

        return $asciiPunctuation;
    }
}
