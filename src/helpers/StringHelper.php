<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use BackedEnum;
use Craft;
use HTMLPurifier_Config;
use Illuminate\Support\Str;
use IteratorAggregate;
use LitEmoji\LitEmoji;
use Normalizer;
use Throwable;
use voku\helper\ASCII;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use const ENT_COMPAT;

/**
 * This helper class provides various multi-byte aware string related manipulation and encoding methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Hamid Sarfraz <http://pageconfig.com/>
 * @author Lars Moelleken <http://www.moelleken.org/>
 * @since 3.0.0
 */
class StringHelper extends \yii\helpers\StringHelper
{
    public const UTF8 = 'UTF-8';

    /**
     * @since 3.0.37
     */
    public const UUID_PATTERN = '[A-Za-z0-9]{8}-[A-Za-z0-9]{4}-4[A-Za-z0-9]{3}-[89abAB][A-Za-z0-9]{3}-[A-Za-z0-9]{12}';

    /**
     * @var array Character mappings
     * @see asciiCharMap()
     */
    private static array $_asciiCharMaps;

    /**
     * @var string[]|false
     * @see escapeShortcodes()
     */
    private static array|false $_shortcodeEscapeMap;

    /**
     * Gets the substring after the first occurrence of a separator.
     *
     * @param string $str The string to search.
     * @param string $separator The separator string.
     * @param bool $caseSensitive Whether to enforce case-sensitivity.
     * @return string The resulting string.
     * @since 3.3.0
     */
    public static function afterFirst(string $str, string $separator, bool $caseSensitive = true): string
    {
        if ($separator === '' || $str === '') {
            return '';
        }

        $offset = $caseSensitive ? mb_strpos($str, $separator) : mb_stripos($str, $separator);

        if ($offset === false) {
            return '';
        }

        return mb_substr($str, $offset + mb_strlen($separator));
    }

    /**
     * Gets the substring after the last occurrence of a separator.
     *
     * @param string $str The string to search.
     * @param string $separator The separator string.
     * @param bool $caseSensitive Whether to enforce case-sensitivity.
     * @return string The resulting string.
     * @since 3.3.0
     */
    public static function afterLast(string $str, string $separator, bool $caseSensitive = true): string
    {
        if ($separator === '' || $str === '') {
            return '';
        }

        $offset = $caseSensitive ? mb_strrpos($str, $separator) : mb_strripos($str, $separator);

        if ($offset === false) {
            return '';
        }

        return mb_substr($str, $offset + mb_strlen($separator));
    }

    /**
     * Returns a new string with $append appended.
     *
     * @param string $str The initial un-appended string.
     * @param string $append The string to append.
     * @return string The newly appended string.
     * @since 3.3.0
     */
    public static function append(string $str, string $append): string
    {
        return $str . $append;
    }

    /**
     * Returns a new string with a random string appended to it.
     *
     * @param string $str The initial un-appended string.
     * @param int $length The length of the random string.
     * @param string $possibleChars The possible random characters to append.
     * @return string The newly appended string.
     * @since 3.3.0
     */
    public static function appendRandomString(string $str, int $length, string $possibleChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): string
    {
        return $str . static::randomStringWithChars($possibleChars, $length);
    }

    /**
     * Returns a new string with a unique identifier appended to it.
     *
     * @param string $str The initial un-appended string.
     * @param string $entropyExtra Extra entropy via a string or int value.
     * @param bool $md5 Whether to return the unique identifier as a md5 hash.
     * @return string The newly appended string.
     * @since 3.3.0
     */
    public static function appendUniqueIdentifier(string $str, string $entropyExtra = '', bool $md5 = true): string
    {
        try {
            $randInt = random_int(0, mt_getrandmax());
        } catch (Throwable) {
            $randInt = mt_rand(0, mt_getrandmax());
        }

        $uniqueHelper = $randInt .
            session_id() .
            ($_SERVER['REMOTE_ADDR'] ?? '') .
            ($_SERVER['SERVER_ADDR'] ?? '') .
            $entropyExtra;

        $uniqueString = uniqid($uniqueHelper, true);

        if ($md5) {
            return md5($uniqueString . $uniqueHelper);
        }

        return $uniqueString;
    }

    /**
     * Returns ASCII character mappings, merging in any custom defined mappings
     * from the <config5:customAsciiCharMappings> config setting.
     *
     * @param bool $flat Whether the mappings should be returned as a flat array (é => e)
     * @param string|null $language Whether to include language-specific mappings (only applied if $flat is true)
     * @return array The fully merged ASCII character mappings.
     */
    public static function asciiCharMap(bool $flat = false, ?string $language = null): array
    {
        $key = $flat ? 'flat-' . ($language ?? '*') : '*';
        if (isset(self::$_asciiCharMaps[$key])) {
            return self::$_asciiCharMaps[$key];
        }

        $map = ASCII::charsArrayWithSingleLanguageValues(false, false);
        if ($language !== null) {
            /** @var ASCII::*_LANGUAGE_CODE $language */
            $langSpecific = ASCII::charsArrayWithOneLanguage($language, false, false);
            if ($langSpecific !== []) {
                $map = array_merge($map, $langSpecific);
            }
        }

        if ($flat) {
            return self::$_asciiCharMaps[$key] = $map;
        }

        $byAscii = [];

        foreach ($map as $char => $ascii) {
            $byAscii[$ascii][] = $char;
        }

        return self::$_asciiCharMaps[$key] = $byAscii;
    }

    /**
     * Returns the character at $index, with indexes starting at 0.
     *
     * @param string $str The initial string to search.
     * @param int $index The position of the character.
     * @return string The resulting character.
     * @since 3.3.0
     */
    public static function at(string $str, int $index): string
    {
        return mb_substr($str, $index, 1);
    }

    /**
     * Gets the substring before the first occurrence of a separator.
     *
     * @param string $str The string to search.
     * @param string $separator The separator string.
     * @param bool $caseSensitive
     * @return string The resulting string.
     * @since 3.3.0
     */
    public static function beforeFirst(string $str, string $separator, bool $caseSensitive = true): string
    {
        if ($separator === '' || $str === '') {
            return '';
        }

        $offset = $caseSensitive ? mb_strpos($str, $separator) : mb_stripos($str, $separator);

        if ($offset === false) {
            return '';
        }

        return mb_substr($str, 0, $offset);
    }

    /**
     * Gets the substring before the last occurrence of a separator.
     *
     * @param string $str The string to search.
     * @param string $separator The separator string.
     * @param bool $caseSensitive
     * @return string The resulting string.
     * @since 3.3.0
     */
    public static function beforeLast(string $str, string $separator, bool $caseSensitive = true): string
    {
        if ($separator === '' || $str === '') {
            return '';
        }

        $offset = $caseSensitive ? mb_strrpos($str, $separator) : mb_strripos($str, $separator);

        if ($offset === false) {
            return '';
        }

        return mb_substr($str, 0, $offset);
    }

    /**
     * Returns the substring between $start and $end, if found, or an empty string.
     * An optional offset may be supplied from which to begin the search for the start string.
     *
     * @param string $str The string to search.
     * @param string $start Delimiter marking the start of the substring.
     * @param string $end Delimiter marking the end of the substring.
     * @param int|null $offset Index from which to begin the search. Defaults to 0.
     * @return string The resulting string.
     */
    public static function between(string $str, string $start, string $end, ?int $offset = null): string
    {
        $startPos = mb_strpos($str, $start, $offset);

        if ($startPos === false) {
            return '';
        }

        $substrIndex = $startPos + mb_strlen($start);
        $endPos = mb_strpos($str, $end, $substrIndex);

        if ($endPos === false || $endPos === $substrIndex) {
            return '';
        }

        return mb_substr($str, $substrIndex, $endPos - $substrIndex);
    }

    /**
     * Returns a camelCase version of the given string. Trims surrounding spaces, capitalizes letters following digits,
     * spaces, dashes and underscores, and removes spaces, dashes, as well as underscores.
     *
     * @param string $str The string to convert to camelCase.
     * @return string The string in camelCase.
     */
    public static function camelCase(string $str): string
    {
        return Str::camel($str);
    }

    /**
     * Returns the string with the first letter of each word capitalized.
     *
     * @param string $str The string to parse.
     * @return string The string with personal names capitalized.
     * @since 3.3.0
     * @deprecated in 5.9.0. Use [[toPascalCase()]] instead.
     */
    public static function capitalizePersonalName(string $str): string
    {
        return static::toPascalCase($str);
    }

    /**
     * Returns an array consisting of the characters in the string.
     *
     * @param string $str
     * @return string[] An array of string chars
     */
    public static function charsAsArray(string $str): array
    {
        return mb_str_split($str);
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a single space. This includes tabs and
     * newline characters, as well as multibyte whitespace such as the thin space and ideographic space.
     *
     * @param string $str The string to remove the whitespace from.
     * @return string The trimmed string with condensed whitespace.
     */
    public static function collapseWhitespace(string $str): string
    {
        return trim(mb_ereg_replace('[[:space:]]+', ' ', $str));
    }

    /**
     * Returns true if the string contains $needle, false otherwise. By default, the comparison is case-sensitive, but
     * can be made insensitive by setting $caseSensitive to false.
     *
     * @param string $haystack The string being checked.
     * @param string $needle The substring to look for.
     * @param bool $caseSensitive Whether to force case-sensitivity.
     * @return bool Whether $haystack contains $needle.
     */
    public static function contains(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        if (!$caseSensitive) {
            // mb_strtolower() isn't as reliable on PHP 8.2
            $haystack = mb_strtoupper($haystack);
            $needle = mb_strtoupper($needle);
        }

        return str_contains($haystack, $needle);
    }

    /**
     * Detects whether the given string has any 4-byte UTF-8 characters.
     *
     * @param string $str The string to process.
     * @return bool Whether the string contains any 4-byte UTF-8 characters or not.
     */
    public static function containsMb4(string $str): bool
    {
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            if (ord($str[$i]) >= 240) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the string contains all $needles, false otherwise. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string $haystack The string being checked.
     * @param string[] $needles The substrings to look for.
     * @param bool $caseSensitive Whether to force case-sensitivity.
     * @return bool Whether $haystack contains all $needles.
     */
    public static function containsAll(string $haystack, array $needles, bool $caseSensitive = true): bool
    {
        if (empty($needles)) {
            return false;
        }

        foreach ($needles as $needle) {
            if (!static::contains($haystack, $needle, $caseSensitive)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if the string contains any $needles, false otherwise. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string $haystack The string being checked.
     * @param string[] $needles The substrings to look for.
     * @param bool $caseSensitive Whether to force case-sensitivity.
     * @return bool Whether $haystack contains any $needles.
     */
    public static function containsAny(string $haystack, array $needles, bool $caseSensitive = true): bool
    {
        foreach ($needles as $needle) {
            if (static::contains($haystack, $needle, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempts to convert a string to UTF-8 and clean any non-valid UTF-8 characters.
     *
     * @param string $str
     * @return string
     */
    public static function convertToUtf8(string $str): string
    {
        // If it's already a UTF8 string, just clean and return it
        if (static::isUtf8($str)) {
            return HtmlPurifier::cleanUtf8($str);
        }

        // Otherwise set HTMLPurifier to the actual string encoding
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', static::encoding($str));

        // Clean it
        $str = HtmlPurifier::cleanUtf8($str);

        // Convert it to UTF8 if possible
        if (App::checkForValidIconv()) {
            $str = HtmlPurifier::convertToUtf8($str, $config);
        } else {
            $str = mb_convert_encoding($str, self::UTF8);
        }

        return $str;
    }

    /**
     * Converts line breaks to Unix line breaks (LF) within the given string.
     *
     * @param string $str
     * @return string
     * @since 5.9.0
     */
    public static function convertLineBreaks(string $str): string
    {
        return preg_replace('/\R/u', "\n", $str);
    }

    /**
     * Returns the length of the string, implementing the countable interface.
     *
     * @param string $str The string to count.
     * @return int The length of the string.
     * @since 3.3.0
     */
    public static function count(string $str): int
    {
        return mb_strlen($str);
    }

    /**
     * Returns the number of occurrences of $substring in the given string. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string $str The string to search through.
     * @param string $substring The substring to search for.
     * @param bool $caseSensitive Whether to enforce case-sensitivity
     * @return int The number of $substring occurrences.
     */
    public static function countSubstrings(string $str, string $substring, bool $caseSensitive = true): int
    {
        if (!$caseSensitive) {
            // mb_strtolower() isn't as reliable on PHP 8.2
            $str = mb_strtoupper($str);
            $substring = mb_strtoupper($substring);
        }

        return mb_substr_count($str, $substring);
    }

    /**
     * Returns a lowercase and trimmed string separated by dashes. Dashes are
     * inserted before uppercase characters (with the exception of the first
     * character of the string), and in place of spaces as well as underscores.
     *
     * @param string $str The string to dasherize.
     * @return string The dasherized string.
     * @since 3.3.0
     */
    public static function dasherize(string $str): string
    {
        return static::delimit($str, '-');
    }

    /**
     * Base64-decodes and decrypts a string generated by [[encenc()]].
     *
     * @param string $str The string.
     * @return string
     * @throws InvalidConfigException on OpenSSL not loaded
     * @throws Exception on OpenSSL error
     */
    public static function decdec(string $str): string
    {
        if (str_starts_with($str, 'base64:')) {
            $str = base64_decode(substr($str, 7));
        }

        if (str_starts_with($str, 'crypt:')) {
            $str = Craft::$app->getSecurity()->decryptByKey(substr($str, 6));
        }

        return $str;
    }

    /**
     * Returns a lowercase and trimmed string separated by the given delimiter. Delimiters are inserted before
     * uppercase characters (with the exception of the first character of the string), and in place of spaces,
     * dashes, and underscores. Alpha delimiters are not converted to lowercase.
     *
     * @param string $str The string to delimit.
     * @param string $delimiter Sequence used to separate parts of the string
     * @return string The delimited string.
     */
    public static function delimit(string $str, string $delimiter): string
    {
        $str = (string) mb_ereg_replace('\\B(\\p{Lu})', '-\1', trim($str));
        return mb_ereg_replace('[\\-_\\s]+', $delimiter, mb_strtolower($str));
    }

    /**
     * Encrypts and base64-encodes a string.
     *
     * @param string $str the string
     * @return string
     * @throws InvalidConfigException on OpenSSL not loaded
     * @throws Exception on OpenSSL error
     * @see decdec()
     */
    public static function encenc(string $str): string
    {
        return 'base64:' . base64_encode('crypt:' . Craft::$app->getSecurity()->encryptByKey($str));
    }

    /**
     * HTML-encodes any 4-byte UTF-8 characters.
     *
     * @param string $str The string
     * @return string The string with converted 4-byte UTF-8 characters
     * @see http://stackoverflow.com/a/16496730/1688568
     */
    public static function encodeMb4(string $str): string
    {
        // (Logic pulled from WP's wp_encode_emoji() function)
        // UTF-32's hex encoding is the same as HTML's hex encoding.
        // So, by converting from UTF-8 to UTF-32, we magically
        // get the correct hex encoding.
        return static::replaceMb4($str, static function($char) {
            $unpacked = unpack('H*', mb_convert_encoding($char, 'UTF-32', self::UTF8));
            return isset($unpacked[1]) ? '&#x' . ltrim($unpacked[1], '0') . ';' : '';
        });
    }

    /**
     * Gets the encoding of the given string.
     *
     * @param string $str The string to process.
     * @return string The encoding of the string.
     */
    public static function encoding(string $str): string
    {
        return mb_strtolower(mb_detect_encoding($str, mb_detect_order(), true));
    }

    /**
     * Returns true if the string ends with any of $substrings, false otherwise.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param string $str The string to check the end of.
     * @param string[] $substrings Substrings to look for.
     * @param bool $caseSensitive Whether to force case-sensitivity.
     * @return bool Whether $str ends with $substring.
     * @since 3.3.0
     */
    public static function endsWithAny(string $str, array $substrings, bool $caseSensitive = true): bool
    {
        if ($substrings === []) {
            return false;
        }

        foreach ($substrings as $substring) {
            if (static::endsWith($str, $substring, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's prepended.
     *
     * @param string $str The string to modify.
     * @param string $substring The substring to add if not present.
     * @return string The string prefixed by the $substring.
     */
    public static function ensureLeft(string $str, string $substring): string
    {
        if (str_starts_with($str, $substring)) {
            return $str;
        }

        return $substring . $str;
    }

    /**
     * Ensures that the string ends with $substring. If it doesn't, it's appended.
     *
     * @param string $str The string to modify.
     * @param string $substring The substring to add if not present.
     * @return string The string suffixed by the $substring.
     */
    public static function ensureRight(string $str, string $substring): string
    {
        if (str_ends_with($str, $substring)) {
            return $str;
        }

        return $str . $substring;
    }

    /**
     * Create a escape html version of the string via "$this->utf8::htmlspecialchars()".
     *
     * @param string $str The string to modify.
     * @return string The string to escape.
     * @since 3.3.0
     */
    public static function escape(string $str): string
    {
        return htmlspecialchars($str);
    }

    /**
     * Create an extract from a sentence, so if the search-string was found, it try to centered in the output.
     *
     * @param string $str The source string.
     * @param string $search The string to search for.
     * @param int|null $length By default, the length of the text divided by two.
     * @param string $replacerForSkippedText The string to use for skipped text.
     * @return string The string to escape.
     * @since 3.3.0
     */
    public static function extractText(string $str, string $search = '', ?int $length = null, string $replacerForSkippedText = '…'): string
    {
        if ($str === '') {
            return '';
        }

        $trimChars = "\t\r\n -_()!~?=+/*\\,.:;\"'[]{}`&";

        if ($length === null) {
            $length = round(mb_strlen($str) / 2);
        }

        if ($search === '') {
            if ($length > 0) {
                $stringLength = mb_strlen($str);
                $end = ($length - 1) > $stringLength ? $stringLength : ($length - 1);
            } else {
                $end = 0;
            }

            $pos = min(
                mb_strpos($str, ' ', $end),
                mb_strpos($str, '.', $end),
            );

            if ($pos) {
                $strSub = mb_substr($str, 0, $pos);

                if ($strSub === '') {
                    return '';
                }

                return rtrim($strSub, $trimChars) . $replacerForSkippedText;
            }

            return $str;
        }

        $wordPosition = mb_stripos($str, $search);
        $halfSide = (int) ($wordPosition - $length / 2 + mb_strlen($search) / 2);

        $posStart = 0;
        if ($halfSide > 0) {
            $halfText = mb_substr($str, 0, $halfSide);
            if ($halfText !== '') {
                $posStart = max(
                    mb_strrpos($halfText, ' '),
                    mb_strrpos($halfText, '.'),
                );
            }
        }

        if ($wordPosition && $halfSide > 0) {
            $offset = $posStart + $length - 1;
            $real_length = mb_strlen($str);

            if ($offset > $real_length) {
                $offset = $real_length;
            }

            $posEnd = min(
                    mb_strpos($str, ' ', $offset),
                    mb_strpos($str, '.', $offset),
                ) - $posStart;

            if (!$posEnd || $posEnd <= 0) {
                $strSub = mb_substr($str, $posStart, mb_strlen($str));
                if ($strSub !== '') {
                    $extract = $replacerForSkippedText . ltrim($strSub, $trimChars);
                } else {
                    $extract = '';
                }
            } else {
                $strSub = mb_substr($str, $posStart, $posEnd);
                $extract = $replacerForSkippedText . trim($strSub, $trimChars) . $replacerForSkippedText;
            }
        } else {
            $offset = $length - 1;
            $trueLength = mb_strlen($str);

            if ($offset > $trueLength) {
                $offset = $trueLength;
            }

            $posEnd = min(
                mb_strpos($str, ' ', $offset),
                mb_strpos($str, '.', $offset),
            );

            if ($posEnd) {
                $strSub = mb_substr($str, 0, $posEnd);
                $extract = rtrim($strSub, $trimChars) . $replacerForSkippedText;
            } else {
                $extract = $str;
            }
        }

        return $extract;
    }

    /**
     * Returns the first $n characters of the string.
     *
     * @param string $str The string from which to get the substring.
     * @param int $number The Number of chars to retrieve from the start.
     * @return string The first $number characters.
     */
    public static function first(string $str, int $number): string
    {
        if ($str === '' || $number <= 0) {
            return '';
        }

        return mb_substr($str, 0, $number);
    }

    /**
     * Returns whether the given string has any lowercase characters in it.
     *
     * @param string $str The string to check.
     * @return bool If the string has a lowercase character or not.
     */
    public static function hasLowerCase(string $str): bool
    {
        return mb_ereg_match('.*[[:lower:]]', $str);
    }

    /**
     * Returns whether the given string has any uppercase characters in it.
     *
     * @param string $str The string to check.
     * @return bool If the string has an uppercase character or not.
     */
    public static function hasUpperCase(string $str): bool
    {
        return mb_ereg_match('.*[[:upper:]]', $str);
    }

    /**
     * Convert all HTML entities to their applicable characters.
     *
     * @param string $str The string to process.
     * @param int $flags A bitmask of these flags: https://www.php.net/manual/en/function.html-entity-decode.php
     * @return string The decoded string.
     * @since 3.3.0
     */
    public static function htmlDecode(string $str, int $flags = ENT_COMPAT): string
    {
        if (!isset($str[3]) || !str_contains($str, '&')) {
            return $str;
        }

        do {
            $strCompare = $str;

            if (str_contains($str, '&')) {
                if (str_contains($str, '&#')) {
                    $str = (string) preg_replace('/(&#(?:x0*[0-9a-fA-F]{2,6}(?![0-9a-fA-F;])|(?:0*\d{2,6}(?![0-9;]))))/S', '$1;', $str);
                }

                $str = html_entity_decode($str, $flags, 'UTF-8');
            }
        } while ($strCompare !== $str);

        return $str;
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * @param string $str The string to process.
     * @param int $flags A bitmask of these flags: https://www.php.net/manual/en/function.html-entity-encode.php
     * @return string The encoded string.
     * @since 3.3.0
     */
    public static function htmlEncode(string $str, int $flags = ENT_COMPAT): string
    {
        return htmlentities($str, $flags, 'UTF-8');
    }

    /**
     * Capitalizes the first word of the string, replaces underscores with
     * spaces, and strips '_id'.
     *
     * @param string $str The string to process.
     * @return string The humanized string.
     * @since 3.3.0
     */
    public static function humanize(string $str): string
    {
        $str = str_replace(['_id', '_'], ['', ' '], $str);
        return static::upperCaseFirst(trim($str));
    }

    /**
     * Returns the index of the first occurrence of $needle in the string, and false if not found.
     * Accepts an optional offset from which to begin the search.
     *
     * @param string $str The string to check the index of.
     * @param string $needle The substring to look for.
     * @param int $offset The offset from which to search.
     * @param bool $caseSensitive Whether to perform a case-sensitive search or not.
     * @return int|false The occurrence's index if found, otherwise false.
     */
    public static function indexOf(string $str, string $needle, int $offset = 0, bool $caseSensitive = true): int|false
    {
        if ($str === '' && $needle === '') {
            return 0;
        }

        if ($caseSensitive) {
            return mb_strpos($str, $needle, $offset);
        }

        return mb_stripos($str, $needle, $offset);
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search. Offsets may be negative to count from the last character
     * in the string.
     *
     * @param string $str The string to check the last index of.
     * @param string $needle The substring to look for.
     * @param int $offset The offset from which to search.
     * @param bool $caseSensitive Whether to perform a case-sensitive search or not.
     * @return int|false The occurrence's last index if found, otherwise false.
     */
    public static function indexOfLast(string $str, string $needle, int $offset = 0, bool $caseSensitive = true): int|false
    {
        if ($str === '' && $needle === '') {
            return 0;
        }

        if ($caseSensitive) {
            return mb_strrpos($str, $needle, $offset);
        }

        return mb_strripos($str, $needle, $offset);
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * @param string $str The string to insert into.
     * @param string $substring The string to be inserted.
     * @param int $index The 0-based index at which to insert the substring.
     * @return string The resulting string after the insertion
     */
    public static function insert(string $str, string $substring, int $index): string
    {
        $len = mb_strlen($str);
        if ($index > $len) {
            return $str;
        }

        return mb_substr($str, 0, $index) .
            $substring .
            mb_substr($str, $index, $len);
    }

    /**
     * Returns true if the string contains the $pattern, otherwise false.
     *
     * WARNING: Asterisks ("*") are translated into (".*") zero-or-more regular
     * expression wildcards.
     *
     * @param string $str The string to process.
     * @param string $pattern The string or pattern to match against.
     * @return bool Whether we match the provided pattern.
     * @since 3.3.0
     */
    public static function is(string $str, string $pattern): bool
    {
        return Str::is($pattern, $str);
    }

    /**
     * Returns true if the string contains only alphabetic chars, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains only alphabetic chars.
     */
    public static function isAlpha(string $str): bool
    {
        return mb_ereg_match('^[[:alpha:]]*$', $str);
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains only alphanumeric chars.
     */
    public static function isAlphanumeric(string $str): bool
    {
        return mb_ereg_match('^[[:alnum:]]*$', $str);
    }

    /**
     * Returns true if the string is base64 encoded, false otherwise.
     *
     * @param string $str The string to check.
     * @param bool $emptyStringIsValid Whether an empty string is considered valid.
     * @return bool Whether $str is base64 encoded.
     * @since 3.3.0
     */
    public static function isBase64(string $str, bool $emptyStringIsValid = true): bool
    {
        if (!$emptyStringIsValid && $str === '') {
            return false;
        }

        $base64String = base64_decode($str, true);
        return $base64String !== false && base64_encode($base64String) === $str;
    }

    /**
     * Returns true if the string contains only whitespace chars, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains only whitespace characters.
     * @since 3.3.0
     */
    public static function isBlank(string $str): bool
    {
        return mb_ereg_match('^[[:space:]]*$', $str);
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains only hexadecimal chars.
     * @since 3.3.0
     */
    public static function isHexadecimal(string $str): bool
    {
        return mb_ereg_match('^[[:xdigit:]]*$', $str);
    }

    /**
     * Returns true if the string contains HTML-Tags, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains HTML tags.
     * @since 3.3.0
     */
    public static function isHtml(string $str): bool
    {
        if ($str === '') {
            return false;
        }

        // init
        $matches = [];
        $str = static::emojiToShortcodes($str);
        preg_match("/<\\/?\\w+(?:(?:\\s+\\w+(?:\\s*=\\s*(?:\".*?\"|'.*?'|[^'\">\\s]+))?)*\\s*|\\s*)\\/?>/u", $str, $matches);
        return $matches !== [];
    }

    /**
     * Returns true if the string is JSON, false otherwise. Unlike json_decode
     * in PHP 5.x, this method is consistent with PHP 7 and other JSON parsers,
     * in that an empty string is not considered valid JSON.
     *
     * @param string $str The string to check.
     * @param bool $onlyArrayOrObjectResultsAreValid
     * @return bool Whether $str is JSON.
     * @since 3.3.0
     */
    public static function isJson(string $str, bool $onlyArrayOrObjectResultsAreValid = false): bool
    {
        try {
            $decoded = Json::decode($str);
        } catch (InvalidArgumentException) {
            return false;
        }

        if ($decoded === null && strtolower($str) !== 'null') {
            return false;
        }

        if ($onlyArrayOrObjectResultsAreValid && !is_object($decoded) && !is_array($decoded)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the string contains only lower case chars, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str is only lower case characters.
     */
    public static function isLowerCase(string $str): bool
    {
        return mb_ereg_match('^[[:lower:]]*$', $str);
    }

    /**
     * Returns true if the string is serialized, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str is serialized.
     * @since 3.3.0
     */
    public static function isSerialized(string $str): bool
    {
        if ($str === '') {
            return false;
        }

        return $str === 'b:0;' || @unserialize($str, []) !== false;
    }

    /**
     * Returns true if the string contains only upper case chars, false
     * otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains only lower case characters.
     */
    public static function isUpperCase(string $str): bool
    {
        return mb_ereg_match('^[[:upper:]]*$', $str);
    }

    /**
     * Checks if the given string is UTF-8 encoded.
     *
     * @param string $str The string to check.
     * @return bool Whether the string was UTF encoded or not.
     * @since 3.3.0
     */
    public static function isUtf8(string $str): bool
    {
        return mb_check_encoding($str, self::UTF8);
    }

    /**
     * Returns true if the string contains only whitespace chars, false otherwise.
     *
     * @param string $str The string to check.
     * @return bool Whether $str contains only whitespace characters.
     * @since 3.3.0
     * @deprecated in 5.9.0. [[`isBlank()`]] should be used instead.
     */
    public static function isWhitespace(string $str): bool
    {
        return static::isBlank($str);
    }

    /**
     * Returns is the given string matches a v4 UUID pattern.
     *
     * Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x
     * is any hexadecimal digit and y is one of 8, 9, A, or B.
     *
     * @param string $uuid The string to check.
     * @return bool Whether the string matches a v4 UUID pattern.
     */
    public static function isUUID(string $uuid): bool
    {
        return !empty($uuid) && preg_match('/^' . self::UUID_PATTERN . '$/', $uuid);
    }

    /**
     * Returns the last $number characters of the string.
     *
     * @param string $str The string from which to get the substring.
     * @param int $number The Number of chars to retrieve from the end.
     * @return string The last $number characters.
     */
    public static function last(string $str, int $number): string
    {
        if ($str === '' || $number <= 0) {
            return '';
        }

        return mb_substr($str, -$number);
    }

    /**
     * Returns the last $number characters of the string.
     *
     * @param string $str The string from which to get the substring.
     * @param string $needle The substring to look for.
     * @param bool $beforeNeedle
     * @param bool $caseSensetive Whether to perform a case sensitive search.
     * @return string The last $number characters.
     * @since 3.3.0
     */
    public static function lastSubstringOf(string $str, string $needle, bool $beforeNeedle = false, bool $caseSensetive = false): string
    {
        if ($str === '' || $needle === '') {
            return '';
        }

        if ($beforeNeedle) {
            $part = $caseSensetive ? mb_strrchr($str, $needle, $beforeNeedle) : mb_strrichr($str, $needle, $beforeNeedle);
            ;
        } else {
            $part = $caseSensetive ? mb_strrchr($str, $needle) : mb_strrichr($str, $needle);
        }

        return $part === false ? '' : $part;
    }

    /**
     * Returns the length of the string. An alias for PHP's mb_strlen() function.
     *
     * @param string $str The string to get the length of.
     * @return int The number of characters in $str.
     */
    public static function length(string $str): int
    {
        return mb_strlen($str);
    }

    /**
     * Line wrap the string after $limit, but also after the next word.
     *
     * @param string $str The string to process.
     * @param int $limit The number of characters to insert the line wrap.
     * @return string The line wrapped string.
     * @since 3.3.0
     */
    public static function lineWrapAfterWord(string $str, int $limit): string
    {
        return Str::wordWrap($str, $limit) . "\n";
    }

    /**
     * Splits on newlines and carriage returns, returning an array of strings
     * corresponding to the lines in the string.
     *
     * @param string $str The string to split.
     * @return string[] An array of strings.
     */
    public static function lines(string $str): array
    {
        if ($str === '') {
            return [''];
        }

        return mb_split("[\r\n]{1,2}", $str);
    }

    /**
     * Returns the first line of a string.
     *
     * @param string $str
     * @return string
     * @since 5.5.0
     */
    public static function firstLine(string $str): string
    {
        return static::lines($str)[0];
    }

    /**
     * Converts the first character of the supplied string to lower case.
     *
     * @param string $str The string to modify.
     * @return string The string with the first character converted to lowercase.
     */
    public static function lowercaseFirst(string $str): string
    {
        return Str::lcfirst($str);
    }

    /**
     * Pads the string to a given length with $padStr. If length is less than
     * or equal to the length of the string, no padding takes places. The
     * default string used for padding is a space, and the default type (one of
     * 'left', 'right', 'both') is 'right'. Throws an InvalidArgumentException
     * if $padType isn't one of those 3 values.
     *
     * @param string $str The string to process.
     * @param int $length The desired length after padding.
     * @param string $padStr The string used to pad. Defaults to space.
     * @param string $padType 'left', 'right', 'both'. Defaults to 'right'.
     * @return string The padded string.
     * @since 3.3.0
     */
    public static function pad(string $str, int $length, string $padStr = ' ', string $padType = 'right'): string
    {
        return match ($padType) {
            'left' => static::padLeft($str, $length, $padStr),
            'both' => static::padBoth($str, $length, $padStr),
            default => static::padRight($str, $length, $padStr),
        };
    }

    /**
     * Returns a new string of a given length such that both sides of the
     * string are padded. Alias for pad() with a $padType of 'both'.
     *
     * @param string $str The string to process.
     * @param int $length The desired length after padding.
     * @param string $padStr The string used to pad. Defaults to space.
     * @return string The padded string.
     * @since 3.3.0
     */
    public static function padBoth(string $str, int $length, string $padStr = ' '): string
    {
        return Str::padBoth($str, $length, $padStr);
    }

    /**
     * Returns a new string of a given length such that the beginning of the
     * string is padded. Alias for pad() with a $padType of 'left'.
     *
     * @param string $str The string to process.
     * @param int $length The desired length after padding.
     * @param string $padStr The string used to pad. Defaults to space.
     * @return string The padded string.
     * @since 3.3.0
     */
    public static function padLeft(string $str, int $length, string $padStr = ' '): string
    {
        return Str::padLeft($str, $length, $padStr);
    }

    /**
     * Returns a new string of a given length such that the end of the
     * string is padded. Alias for pad() with a $padType of 'right'.
     *
     * @param string $str The string to process.
     * @param int $length The desired length after padding.
     * @param string $padStr The string used to pad. Defaults to space.
     * @return string The padded string.
     * @since 3.3.0
     */
    public static function padRight(string $str, int $length, string $padStr = ' '): string
    {
        return Str::padRight($str, $length, $padStr);
    }

    /**
     * Returns a new string starting with $string.
     *
     * @param string $str The string to process.
     * @param string $string The string to prepend.
     * @return string The full prepended string.
     */
    public static function prepend(string $str, string $string): string
    {
        return $string . $str;
    }

    /**
     * Generates a random string of latin alphanumeric characters that defaults to a $length of 36. If $extendedChars is
     * set to true, additional symbols can be included in the string. Note that the generated string is *not* a
     * cryptographically secure string. If you need a cryptographically secure string, use
     * [[\craft\services\Security::generateRandomString()|`Craft::$app->security->generateRandomString()`]].
     *
     * @param int $length The length of the random string. Defaults to 36.
     * @param bool $extendedChars Whether to include symbols in the random string.
     * @return string The randomly generated string.
     */
    public static function randomString(int $length = 36, bool $extendedChars = false): string
    {
        if ($extendedChars) {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?';
        } else {
            $validChars = 'abcdefghijklmnopqrstuvwxyz';
        }

        return static::randomStringWithChars($validChars, $length);
    }

    /**
     * Generates a random string of characters. Note that the generated string is *not* a
     * cryptographically secure string. If you need a cryptographically secure string, use
     * [[\craft\services\Security::generateRandomString()|`Craft::$app->security->generateRandomString()`]].
     *
     * @param string $validChars A string containing the valid characters
     * @param int $length The length of the random string
     * @return string The randomly generated string.
     */
    public static function randomStringWithChars(string $validChars, int $length): string
    {
        if ($validChars === '') {
            return '';
        }

        $randomString = '';

        // count the number of chars in the valid chars string so we know how many choices we have
        $chars = static::charsAsArray($validChars);
        $numValidChars = count($chars);

        // repeat the steps until we've created a string of the right length
        for ($i = 0; $i < $length; $i++) {
            // pick a random number from 1 up to the number of valid chars
            $randomPick = random_int(0, $numValidChars - 1);

            // take the random character out of the string of valid chars
            $randomChar = $chars[$randomPick];

            // add the randomly-chosen char onto the end of our string
            $randomString .= $randomChar;
        }

        return $randomString;
    }

    /**
     * Replaces all occurrences of $pattern in $str by $replacement. An alias for mb_ereg_replace().
     *
     * @param string $str The haystack to search through.
     * @param string $pattern The regular expression pattern.
     * @param string $replacement The string to replace with.
     * @param string $options Matching conditions to be used. Defaults to 'msr'. See
     * [here](https://php.net/manual/en/function.mb-ereg-replace.php) for all options.
     * @return string The resulting string after the replacements.
     */
    public static function regexReplace(string $str, string $pattern, string $replacement, string $options = 'msr'): string
    {
        if ($options === 'msr') {
            $options = 'ms';
        }

        return (string) preg_replace("/$pattern/u$options", $replacement, $str);
    }

    /**
     * Remove html via "strip_tags()" from the string.
     *
     * @param string $str The string to process.
     * @param string|null $allowableTags Tags that should not be stripped.
     * @return string The string with Html removed.
     * @since 3.3.0
     */
    public static function removeHtml(string $str, ?string $allowableTags = null): string
    {
        return strip_tags($str, $allowableTags);
    }

    /**
     * Remove all breaks [<br> | \r\n | \r | \n | ...] from the string.
     *
     * @param string $str The string to process.
     * @param string $replacement The optional string to replace with.
     * @return string The string with Html breaks removed.
     * @since 3.3.0
     */
    public static function removeHtmlBreak(string $str, string $replacement = ''): string
    {
        return preg_replace("#/\r\n|\r|\n|<br.*/?>#isU", $replacement, $str);
    }

    /**
     * Returns a new string with the prefix $substring removed, if present.
     *
     * @param string $str The string from which to remove the prefix.
     * @param string $substring The prefix to remove.
     * @return string The string without the prefix $substring.
     */
    public static function removeLeft(string $str, string $substring): string
    {
        if ($substring && str_starts_with($str, $substring)) {
            return mb_substr($str, mb_strlen($substring));
        }

        return $str;
    }

    /**
     * Returns a new string with the suffix $substring removed, if present.
     *
     * @param string $str The string from which to remove the suffix.
     * @param string $substring The suffix to remove.
     * @return string The string without the suffix $substring.
     */
    public static function removeRight(string $str, string $substring): string
    {
        if ($substring && str_ends_with($str, $substring)) {
            return mb_substr($str, 0, mb_strlen($str) - mb_strlen($substring));
        }

        return $str;
    }

    /**
     * Returns a repeated string given a multiplier.
     *
     * @param string $str The string to process.
     * @param int $multiplier The number of times to repeat the string.
     * @return string The string without the suffix $substring.
     * @since 3.3.0
     */
    public static function repeat(string $str, int $multiplier): string
    {
        return str_repeat($str, $multiplier);
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * @param string $str The haystack to search through.
     * @param string $search The needle to search for.
     * @param string $replacement The string to replace with.
     * @return string The resulting string after the replacements.
     */
    public static function replace(string $str, string $search, string $replacement): string
    {
        return str_replace($search, $replacement, $str);
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * @param string $str The haystack to search through.
     * @param string[] $search The needle(s) to search for.
     * @param string|string[] $replacement The string(s) to replace with.
     * @param bool $caseSensitive Whether to perform a case-sensitive search.
     * @return string The resulting string after the replacements.
     * @since 3.3.0
     */
    public static function replaceAll(string $str, array $search, string|array $replacement, bool $caseSensitive = true): string
    {
        if ($caseSensitive) {
            return str_replace($search, $replacement, $str);
        }

        // str_ireplace() doesn't handle multibyte characters properly
        foreach ($search as &$s) {
            if ($s === '') {
                $s = '/^(?<=.)$/';
            } else {
                $s = '/' . preg_quote($s, '/') . '/ui';
            }
        }

        return preg_replace($search, $replacement, $str);
    }

    /**
     * Replaces all occurrences of $search from the beginning of string with $replacement.
     *
     * @param string $str The haystack to search through.
     * @param string $search The needle to search for.
     * @param string $replacement The string to replace with.
     * @return string The resulting string after the replacements.
     * @since 3.3.0
     */
    public static function replaceBeginning(string $str, string $search, string $replacement): string
    {
        if ($search === '') {
            return $replacement . $str;
        }

        return Str::replaceStart($search, $replacement, $str);
    }

    /**
     * Replaces all occurrences of $search from the beginning of string with $replacement.
     *
     * @param string $str The haystack to search through.
     * @param string $search The needle to search for.
     * @param string $replacement The string to replace with.
     * @return string The resulting string after the replacements.
     * @since 3.3.0
     */
    public static function replaceEnding(string $str, string $search, string $replacement): string
    {
        if ($search === '') {
            return $str . $replacement;
        }

        return Str::replaceEnd($search, $replacement, $str);
    }

    /**
     * Replaces first occurrences of $search from the beginning of string with $replacement.
     *
     * @param string $str The haystack to search through.
     * @param string $search The needle to search for.
     * @param string $replacement The string to replace with.
     * @return string The resulting string after the replacements.
     * @since 3.3.0
     */
    public static function replaceFirst(string $str, string $search, string $replacement): string
    {
        return Str::replaceFirst($search, $replacement, $str);
    }

    /**
     * Replaces last occurrences of $search from the ending of string with $replacement.
     *
     * @param string $str The haystack to search through.
     * @param string $search The needle to search for.
     * @param string $replacement The string to replace with.
     * @return string The resulting string after the replacements.
     * @since 3.3.0
     */
    public static function replaceLast(string $str, string $search, string $replacement): string
    {
        return Str::replaceLast($search, $replacement, $str);
    }

    /**
     * Replaces 4-byte UTF-8 characters in a string.
     * ---
     * ```php
     * // Convert emojis to smilies
     * $string = StringHelper::replaceMb4($string, function($char) {
     *     switch ($char) {
     *         case '😀':
     *             return ':)';
     *         case '☹️':
     *             return ':(';
     *         default:
     *             return '¯\_(ツ)_/¯';
     *     }
     * });
     * ```
     *
     * @param string $str The string
     * @param callable|string $replace The replacement string, or callback function.
     * @return string The string with converted 4-byte UTF-8 characters
     * @since 3.1.13
     */
    public static function replaceMb4(string $str, callable|string $replace): string
    {
        $r = preg_replace_callback('/./u', function(array $match) use ($replace): string {
            if (strlen($match[0]) >= 4) {
                return is_callable($replace) ? $replace($match[0]) : $replace;
            }
            return $match[0];
        }, $str);
        if ($r === null) {
            $message = match (preg_last_error()) {
                PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
                default => 'Invalid string',
            };
            throw new InvalidArgumentException($message);
        }
        return $r;
    }

    /**
     * Returns a reversed string. A multibyte version of strrev().
     *
     * @param string $str The string to reverse.
     * @return string The reversed string.
     */
    public static function reverse(string $str): string
    {
        return Str::reverse($str);
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not split words. If $substring is provided,
     * and truncating occurs, the string is further truncated so that the substring may be appended without exceeding
     * the desired length.
     *
     * @param string $str The string to truncate.
     * @param int $length The desired length of the truncated string.
     * @param string $substring The substring to append if it can fit.
     * @param bool $ignoreDoNotSplitWordsForOneWord
     * @return string The resulting string after truncating.
     * @since 3.3.0
     */
    public static function safeTruncate(string $str, int $length, string $substring = '', bool $ignoreDoNotSplitWordsForOneWord = true): string
    {
        if ($str === '' || $length <= 0) {
            return $substring;
        }

        if ($length >= mb_strlen($str)) {
            return $str;
        }

        // need to further trim the string so we can append the substring
        $length -= mb_strlen($substring);
        if ($length <= 0) {
            return $substring;
        }

        $truncated = mb_substr($str, 0, $length);
        if ($truncated === '') {
            return '';
        }

        // if the last word was truncated
        $spacePosition = mb_strpos($str, ' ', $length - 1);
        if ($spacePosition !== $length) {
            // find pos of the last occurrence of a space, get up to that
            $last_position = mb_strrpos($truncated, ' ', 0);

            if (
                $last_position !== false ||
                ($spacePosition !== false && !$ignoreDoNotSplitWordsForOneWord)
            ) {
                $truncated = mb_substr($truncated, 0, (int) $last_position);
            }
        }

        return $truncated . $substring;
    }

    /**
     * Shorten the string after $length, but also after the next word.
     *
     * @param string $str The string to process
     * @param int $length The length to start the shortening.
     * @param string $strAddOn The character to use after the length.
     * @return string The shortened string.
     * @since 3.3.0
     */
    public static function shortenAfterWord(string $str, int $length, string $strAddOn = '…'): string
    {
        if ($str === '' || $length <= 0) {
            return '';
        }

        if (mb_strlen($str) <= $length) {
            return $str;
        }

        if (mb_substr($str, $length - 1, 1) === ' ') {
            return (mb_substr($str, 0, $length - 1)) . $strAddOn;
        }

        $str = mb_substr($str, 0, $length);
        if ($str === '') {
            return $strAddOn;
        }

        $array = explode(' ', $str, -1);
        $new_str = implode(' ', $array);

        if ($new_str === '') {
            return (mb_substr($str, 0, $length - 1)) . $strAddOn;
        }

        return $new_str . $strAddOn;
    }

    /**
     * Shorten the string after $length, but also after the next word.
     *
     * @param string $str The string to process
     * @return string The shortened string.
     * @since 3.3.0
     */
    public static function shuffle(string $str): string
    {
        $indexes = range(0, mb_strlen($str) - 1);
        shuffle($indexes);

        $shuffledStr = '';

        foreach ($indexes as $i) {
            $shuffledStr .= mb_substr($str, $i, 1);
        }

        return $shuffledStr;
    }

    /**
     * Returns the substring beginning at $start, and up to, but not including
     * the index specified by $end. If $end is omitted, the function extracts
     * the remaining string. If $end is negative, it is computed from the end
     * of the string.
     *
     * @param string $str The string to process
     * @param int $start Index from which to begin the extraction.
     * @param int|null $end Index at which to end the extraction.
     * @return string The extracted substring.
     * @since 3.3.0
     */
    public static function slice(string $str, int $start, ?int $end = null): string
    {
        if ($end === null) {
            $length = mb_strlen($str);
        } elseif ($end >= 0 && $end <= $start) {
            return '';
        } elseif ($end < 0) {
            $length = mb_strlen($str) + $end - $start;
        } else {
            $length = $end - $start;
        }

        return mb_substr($str, $start, $length);
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $replacement. The replacement defaults to a single dash, and the string
     * is also converted to lowercase. The language of the source string can
     * also be supplied for language-specific transliteration.
     *
     * @param string $str The string to process
     * @param string $replacement The string used to replace whitespace.
     * @param string|null $language The language of the source string.
     * @return string The string converted to a URL slug.
     * @since 3.3.0
     */
    public static function slugify(string $str, string $replacement = '-', ?string $language = null): string
    {
        $language ??= Craft::$app->language;
        return Str::slug($str, $replacement, $language);
    }

    /**
     * Splits a string into chunks on a given delimiter.
     *
     * @param string $str The string
     * @param string $delimiter The delimiter to split the string on (defaults to a comma)
     * @return string[] The segments of the string.
     * @since 3.3.0
     */
    public static function split(string $str, string $delimiter = ','): array
    {
        return preg_split('/\s*' . preg_quote($delimiter, '/') . '\s*/', $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Splits a string into an array of the words in the string.
     *
     * @param string $str The string
     * @return string[] The words in the string
     */
    public static function splitOnWords(string $str): array
    {
        // Split on anything that is not alphanumeric, or a period, underscore, or hyphen.
        // Reference: http://www.regular-expressions.info/unicode.html
        preg_match_all('/[\p{L}\p{N}\p{M}\._-]+/u', $str, $matches);

        return ArrayHelper::filterEmptyStringsFromArray($matches[0]);
    }

    /**
     * Returns true if the string begins with any of $substrings, false otherwise.
     * By default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * @param string $str The string to check the start of.
     * @param string[] $substrings The substrings to look for.
     * @param bool $caseSensitive Whether to enforce case-sensitivity.
     * @return bool Whether $str starts with $substring.
     * @since 3.3.0
     */
    public static function startsWithAny(string $str, array $substrings, bool $caseSensitive = true): bool
    {
        foreach ($substrings as $substring) {
            if (static::startsWith($str, $substring, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove CSS media-queries.
     *
     * @param string $str The string to process.
     * @return string The string, sans any CSS media queries.
     * @since 3.3.0
     */
    public static function stripCssMediaQueries(string $str): string
    {
        return preg_replace('#@media\\s+(?:only\\s)?(?:[\\s{(]|screen|all)\\s?[^{]+{.*}\\s*}\\s*#isumU', '', $str);
    }

    /**
     * Remove any empty HTML tags.
     *
     * @param string $str The string to process.
     * @return string The string, sans any empty HTML tags.
     * @since 3.3.0
     */
    public static function stripEmptyHtmlTags(string $str): string
    {
        return preg_replace('/<[^\\/>]*?>\\s*?<\\/[^>]*?>/u', '', $str);
    }

    /**
     * Strips HTML tags out of a given string.
     *
     * @param string $str The string.
     * @return string The string, sans-HTML
     * @since 3.3.0
     */
    public static function stripHtml(string $str): string
    {
        return preg_replace('/<(.*?)>/u', '', $str);
    }

    /**
     * Strip all whitespace characters. This includes tabs and newline characters,
     * as well as multibyte whitespace such as the thin space and ideographic space.
     *
     * @param string $str The string.
     * @return string The string, sans-whitespace.
     * @since 3.3.0
     */
    public static function stripWhitespace(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return preg_replace('/[[:space:]]+/u', '', $str);
    }

    /**
     * Returns the substring beginning at $start with the specified|null $length. It differs from the mb_substr() function in
     * that providing a|null $length of null will return the rest of the string, rather than an empty string.
     *
     * @param string $str The string to get the length of.
     * @param int $start Position of the first character to use.
     * @param int|null $length Maximum number of characters used.
     * @return string The substring of $str.
     */
    public static function substr(string $str, int $start, ?int $length = null): string
    {
        if ($str === '' || $length === 0) {
            return '';
        }

        return mb_substr($str, $start, $length);
    }

    /**
     * Gets the substring after (or before via "$beforeNeedle") the first occurrence of the "$needle".
     * If no match is found, returns an empty string.
     *
     * @param string $str The string to process.
     * @param string $needle The string to look for.
     * @param bool $beforeNeedle
     * @param bool $caseSensitive Whether to perform a case-sensitive search or not.
     * @return string The substring of $str.
     * @since 3.3.0
     */
    public static function substringOf(string $str, string $needle, bool $beforeNeedle = false, bool $caseSensitive = false): string
    {
        if ($str === '' || $needle === '') {
            return '';
        }

        $part = $caseSensitive ? mb_strstr($str, $needle, $beforeNeedle) : mb_stristr($str, $needle, $beforeNeedle);
        return $part === false ? '' : $part;
    }

    /**
     * Surrounds $str with the given substring.
     *
     * @param string $str The string to process.
     * @param string $substring The substring to add to both sides.
     * @return string The string with the substring both prepended and appended.
     * @since 3.3.0
     */
    public static function surround(string $str, string $substring): string
    {
        return Str::wrap($str, $substring);
    }

    /**
     * Returns a case swapped version of the string.
     *
     * @param string $str The string to swap case.
     * @return string The string with each character's case swapped.
     */
    public static function swapCase(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return mb_strtolower($str) ^ mb_strtoupper($str) ^ $str;
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * @param string $str The string to tidy.
     * @return string The tidy string.
     * @since 3.3.0
     */
    public static function tidy(string $str): string
    {
        return ASCII::normalize_msword($str);
    }

    /**
     * Returns a trimmed string with the first letter of each word capitalized. Ignores the case of other letters,
     * preserving any acronyms. Also accepts an array, $ignore, allowing you to list words not to be capitalized.
     *
     * @param string $str The string to titleize.
     * @param string[]|null $ignore An array of words not to capitalize.
     * @return string The titleized string.
     */
    public static function titleize(string $str, ?array $ignore = null): string
    {
        if ($str === '') {
            return '';
        }

        $smallWords = [
            '(?<!q&)a',
            'an',
            'and',
            'as',
            'at(?!&t)',
            'but',
            'by',
            'en',
            'for',
            'if',
            'in',
            'of',
            'on',
            'or',
            'the',
            'to',
            'v[.]?',
            'via',
            'vs[.]?',
        ];

        $ignore ??= [];
        if ($ignore !== []) {
            $smallWords = array_merge($smallWords, $ignore);
        }

        $smallWordsRx = implode('|', $smallWords);
        $apostropheRx = '(?x: [\'’] [[:lower:]]* )?';

        $str = trim($str);

        if (!static::hasLowerCase($str)) {
            $str = Str::lower($str);
        }

        // the main substitutions
        $str = (string) preg_replace_callback(
            '~\\b (_*) (?:                                                                  # 1. Leading underscore and
                        ( (?<=[ ][/\\\\]) [[:alpha:]]+ [-_[:alpha:]/\\\\]+ |                # 2. file path or
                          [-_[:alpha:]]+ [@.:] [-_[:alpha:]@.:/]+ ' . $apostropheRx . ' )  #    URL, domain, or email
                        |                                                                   #
                        ( (?i: ' . $smallWordsRx . ' ) ' . $apostropheRx . ' )           # 3. or small word (case-insensitive)
                        |                                                                   #
                        ( [[:alpha:]] [[:lower:]\'’()\[\]{}]* ' . $apostropheRx . ' )      # 4. or word w/o internal caps
                        |                                                                   #
                        ( [[:alpha:]] [[:alpha:]\'’()\[\]{}]* ' . $apostropheRx . ' )      # 5. or some other word
                      ) (_*) \\b                                                            # 6. With trailing underscore
                    ~ux',
            function(array $matches): string {
                // preserve leading underscore
                $str = $matches[1];
                if ($matches[2]) {
                    // preserve URLs, domains, emails and file paths
                    $str .= $matches[2];
                } elseif ($matches[3]) {
                    // lower-case small words
                    $str .= Str::lower($matches[3]);
                } elseif ($matches[4]) {
                    // capitalize word w/o internal caps
                    $str .= Str::ucfirst($matches[4]);
                } else {
                    // preserve other kinds of word (iPhone)
                    $str .= $matches[5];
                }
                // preserve trailing underscore
                $str .= $matches[6];

                return $str;
            },
            $str,
        );

        // Exceptions for small words: capitalize at start of title...
        $str = preg_replace_callback(
            '~(  \\A [[:punct:]]*            # start of title...
                      |  [:.;?!][ ]+                # or of subsentence...
                      |  [ ][\'"“‘(\[][ ]* )        # or of inserted subphrase...
                      ( ' . $smallWordsRx . ' ) \\b # ...followed by small word
                     ~uxi',
            fn(array $matches) => $matches[1] . Str::ucfirst($matches[2]),
            $str,
        );

        // ...and end of title
        $str = preg_replace_callback(
            '~\\b ( ' . $smallWordsRx . ' ) # small word...
                      (?= [[:punct:]]* \Z          # ...at the end of the title...
                      |   [\'"’”)\]] [ ] )         # ...or of an inserted subphrase?
                     ~uxi',
            fn(array $matches) => Str::ucfirst($matches[1]),
            $str,
        );

        // Exceptions for small words in hyphenated compound words.
        // e.g. "in-flight" -> In-Flight
        $str = preg_replace_callback(
            '~\\b
                        (?<! -)                   # Negative lookbehind for a hyphen; we do not want to match man-in-the-middle but do want (in-flight)
                        ( ' . $smallWordsRx . ' )
                        (?= -[[:alpha:]]+)        # lookahead for "-someword"
                       ~uxi',
            fn(array $matches) => Str::ucfirst($matches[1]),
            $str,
        );

        // e.g. "Stand-in" -> "Stand-In" (Stand is already capped at this point)
        return preg_replace_callback(
            '~\\b
                      (?<!…)                    # Negative lookbehind for a hyphen; we do not want to match man-in-the-middle but do want (stand-in)
                      ( [[:alpha:]]+- )         # $1 = first word and hyphen, should already be properly capped
                      ( ' . $smallWordsRx . ' ) # ...followed by small word
                      (?!	- )                 # Negative lookahead for another -
                     ~uxi',
            fn(array $matches) => $matches[1] . Str::ucfirst($matches[2]),
            $str,
        );
    }

    /**
     * Returns a trimmed string in proper title case.
     *
     * Also accepts an array, $ignore, allowing you to list words not to be
     * capitalized.
     *
     * Adapted from John Gruber's script.
     *
     * @see https://gist.github.com/gruber/9f9e8650d68b13ce4d78
     * @param string $str The string to titleize.
     * @param string[] $ignore An array of words not to capitalize.
     * @return string The titleized string.
     * @since 3.3.0
     */
    public static function titleizeForHumans(string $str, array $ignore = []): string
    {
        if ($str === '') {
            return '';
        }

        $smallWords = [
            '(?<!q&)a',
            'an',
            'and',
            'as',
            'at(?!&t)',
            'but',
            'by',
            'en',
            'for',
            'if',
            'in',
            'of',
            'on',
            'or',
            'the',
            'to',
            'v[.]?',
            'via',
            'vs[.]?',
        ];

        if ($ignore !== []) {
            $smallWords = array_merge($smallWords, $ignore);
        }

        $smallWordsRx = implode('|', $smallWords);
        $apostropheRx = '(?x: [\'’] [[:lower:]]* )?';

        $str = trim($str);

        if (!static::hasLowerCase($str)) {
            $str = Str::lower($str);
        }

        // the main substitutions
        $str = preg_replace_callback(
            '~\\b (_*) (?:                                                                  # 1. Leading underscore and
                        ( (?<=[ ][/\\\\]) [[:alpha:]]+ [-_[:alpha:]/\\\\]+ |                # 2. file path or
                          [-_[:alpha:]]+ [@.:] [-_[:alpha:]@.:/]+ ' . $apostropheRx . ' )  #    URL, domain, or email
                        |                                                                   #
                        ( (?i: ' . $smallWordsRx . ' ) ' . $apostropheRx . ' )           # 3. or small word (case-insensitive)
                        |                                                                   #
                        ( [[:alpha:]] [[:lower:]\'’()\[\]{}]* ' . $apostropheRx . ' )      # 4. or word w/o internal caps
                        |                                                                   #
                        ( [[:alpha:]] [[:alpha:]\'’()\[\]{}]* ' . $apostropheRx . ' )      # 5. or some other word
                      ) (_*) \\b                                                            # 6. With trailing underscore
                    ~ux',
            function(array $matches): string {
                // preserve leading underscore
                $str = $matches[1];
                if ($matches[2]) {
                    // preserve URLs, domains, emails and file paths
                    $str .= $matches[2];
                } elseif ($matches[3]) {
                    // lower-case small words
                    $str .= Str::lower($matches[3]);
                } elseif ($matches[4]) {
                    // capitalize word w/o internal caps
                    $str .= Str::ucfirst($matches[4]);
                } else {
                    // preserve other kinds of word (iPhone)
                    $str .= $matches[5];
                }
                // preserve trailing underscore
                $str .= $matches[6];

                return $str;
            },
            $str,
        );

        // Exceptions for small words: capitalize at start of title...
        $str = preg_replace_callback(
            '~(  \\A [[:punct:]]*            # start of title...
                      |  [:.;?!][ ]+                # or of subsentence...
                      |  [ ][\'"“‘(\[][ ]* )        # or of inserted subphrase...
                      ( ' . $smallWordsRx . ' ) \\b # ...followed by small word
                     ~uxi',
            fn(array $matches) => $matches[1] . Str::ucfirst($matches[2]),
            $str,
        );

        // ...and end of title
        $str = preg_replace_callback(
            '~\\b ( ' . $smallWordsRx . ' ) # small word...
                      (?= [[:punct:]]* \Z          # ...at the end of the title...
                      |   [\'"’”)\]] [ ] )         # ...or of an inserted subphrase?
                     ~uxi',
            fn(array $matches) => Str::ucfirst($matches[1]),
            $str
        );

        // Exceptions for small words in hyphenated compound words.
        // e.g. "in-flight" -> In-Flight
        $str = preg_replace_callback(
            '~\\b
                        (?<! -)                   # Negative lookbehind for a hyphen; we do not want to match man-in-the-middle but do want (in-flight)
                        ( ' . $smallWordsRx . ' )
                        (?= -[[:alpha:]]+)        # lookahead for "-someword"
                       ~uxi',
            fn(array $matches) => Str::ucfirst($matches[1]),
            $str,
        );

        // e.g. "Stand-in" -> "Stand-In" (Stand is already capped at this point)
        return preg_replace_callback(
            '~\\b
                      (?<!…)                    # Negative lookbehind for a hyphen; we do not want to match man-in-the-middle but do want (stand-in)
                      ( [[:alpha:]]+- )         # $1 = first word and hyphen, should already be properly capped
                      ( ' . $smallWordsRx . ' ) # ...followed by small word
                      (?!	- )                 # Negative lookahead for another -
                     ~uxi',
            fn(array $matches) => $matches[1] . Str::ucfirst($matches[2]),
            $str,
        );
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are replaced with their closest ASCII
     * counterparts, and the rest are removed.
     *
     * @param string $str The string to convert.
     * @param string|null $language The language to pull ASCII character mappings for.
     * @return string The string that contains only ASCII characters.
     */
    public static function toAscii(string $str, ?string $language = null): string
    {
        // Normalize NFD chars to NFC
        $str = Normalizer::normalize($str, Normalizer::FORM_C);
        $language ??= Craft::$app->language;
        return ASCII::to_ascii($str, $language);
    }

    /**
     * Returns a boolean representation of the given logical string value.
     * For example, 'true', '1', 'on' and 'yes' will return true. 'false', '0',
     * 'off', and 'no' will return false. In all instances, case is ignored.
     * For other numeric strings, their sign will determine the return value.
     * In addition, blank strings consisting of only whitespace will return
     * false. For all other strings, the return value is a result of a
     * boolean cast.
     *
     * @param string $str The string to process.
     * @return bool A boolean value for the string.
     * @since 3.3.0
     */
    public static function toBoolean(string $str): bool
    {
        return App::parseBooleanEnv($str) ?? false;
    }

    /**
     * camelCases a string.
     *
     * @param string $str The string to camelize.
     * @return string The string camelized.
     */
    public static function toCamelCase(string $str): string
    {
        return Str::camel($str);
    }

    /**
     * kebab-cases a string.
     *
     * @param string $str The string the process.
     * @param string $glue The string used to glue the words together (default is a hyphen)
     * @param bool $lower Whether the string should be lowercased (default is true)
     * @param bool $removePunctuation Whether punctuation marks should be removed (default is true)
     * @return string The kebab-cased string.
     */
    public static function toKebabCase(string $str, string $glue = '-', bool $lower = true, bool $removePunctuation = true): string
    {
        $words = self::toWords($str, $lower, $removePunctuation);
        $words = ArrayHelper::filterEmptyStringsFromArray(array_map(fn($str) => trim($str, $glue), $words));

        return implode($glue, $words);
    }

    /**
     * Converts all characters in the string to lowercase. An alias for PHP's mb_strtolower().
     *
     * @param string $str The string to convert to lowercase.
     * @return string The lowercase string.
     */
    public static function toLowerCase(string $str): string
    {
        return Str::lower($str);
    }

    /**
     * PascalCases a string.
     *
     * @param string $str The string to process.
     * @return string
     */
    public static function toPascalCase(string $str): string
    {
        $words = self::toWords($str, true, true);
        return implode('', array_map([
            static::class,
            'upperCaseFirst',
        ], $words));
    }

    /**
     * snake_cases a string.
     *
     * @param string $str The string to snakeize.
     * @return string The snakeized string.
     */
    public static function toSnakeCase(string $str): string
    {
        return Str::snake($str);
    }

    /**
     * Converts each tab in the string to some number of spaces, as defined by
     * $tabLength. By default, each tab is converted to 4 consecutive spaces.
     *
     * @param string $str The string to process.
     * @param int $tabLength The number of spaces to replace each tab with. Defaults to four.
     * @return string The string with tabs converted to spaces.
     * @since 3.3.0
     */
    public static function toSpaces(string $str, int $tabLength = 4): string
    {
        $tab = str_repeat(' ', $tabLength);
        return str_replace("\t", $tab, $str);
    }

    /**
     * Converts an object to its string representation. If the object is an array, will glue the array elements together
     * with the $glue param. Otherwise will cast the object to a string.
     *
     * @param mixed $object The object to convert to a string.
     * @param string $glue The glue to use if the object is an array.
     * @return string The string representation of the object.
     */
    public static function toString(mixed $object, string $glue = ','): string
    {
        if (is_scalar($object) || (is_object($object) && method_exists($object, '__toString'))) {
            return (string)$object;
        }

        if (is_array($object) || $object instanceof IteratorAggregate) {
            $stringValues = [];

            foreach ($object as $value) {
                if (($value = static::toString($value, $glue)) !== '') {
                    $stringValues[] = $value;
                }
            }

            return implode($glue, $stringValues);
        }

        if ($object instanceof BackedEnum) {
            return $object->value;
        }

        return '';
    }

    /**
     * Converts each occurrence of some consecutive number of spaces, as
     * defined by $tabLength, to a tab. By default, each 4 consecutive spaces
     * are converted to a tab.
     *
     * @param string $str The string to process.
     * @param int $tabLength The number of spaces to replace with a tab. Defaults to four.
     * @return string The string with spaces converted to tabs.
     * @since 3.3.0
     */
    public static function toTabs(string $str, int $tabLength = 4): string
    {
        $tab = str_repeat(' ', $tabLength);
        return str_replace($tab, "\t", $str);
    }

    /**
     * Converts the first character of each word in the string to uppercase.
     *
     * @param string $str The string to convert case.
     * @return string The title-cased string.
     */
    public static function toTitleCase(string $str): string
    {
        return Str::title($str);
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * @param string $str The string to transliterate.
     * @param bool $strict Use "transliterator_transliterate()" from the PHP intl extension.
     * @return string The transliterated string.
     * @since 3.3.0
     */
    public static function toTransliterate(string $str, bool $strict = false): string
    {
        return ASCII::to_transliterate($str, strict: $strict);
    }

    /**
     * Converts all characters in the string to uppercase. An alias for PHP's mb_strtoupper().
     *
     * @param string $str The string to convert to uppercase.
     * @return string The uppercase string.
     */
    public static function toUpperCase(string $str): string
    {
        return Str::upper($str);
    }

    /**
     * Returns an array of words extracted from a string
     *
     * @param string $str The string
     * @param bool $lower Whether the returned words should be lowercased
     * @param bool $removePunctuation Whether punctuation should be removed from the returned words
     * @return string[] The prepped words in the string
     * @since 3.1.0
     */
    public static function toWords(string $str, bool $lower = false, bool $removePunctuation = false): array
    {
        // Convert CamelCase to multiple words
        // Regex copied from Inflector::camel2words(), but without dropping punctuation
        $str = preg_replace('/(?<!\p{Lu})(\p{Lu})|(\p{Lu})(?=\p{Ll})/u', ' \0', $str);

        if ($lower) {
            // Make it lowercase
            $str = mb_strtolower($str);
        }

        if ($removePunctuation) {
            $str = str_replace(['.', '_', '-'], ' ', $str);
        }

        // Remove inner-word punctuation.
        $str = preg_replace('/[\'"‘’“”ʻ\[\]\(\)\{\}:]/u', '', $str);

        // Split on the words and return
        return static::splitOnWords($str);
    }

    /**
     * Returns a handle-safe version of a string.
     *
     * @param string $str
     * @return string
     * @since 4.4.0
     */
    public static function toHandle(string $str): string
    {
        // Remove HTML tags
        $handle = static::stripHtml($str);

        // Remove inner-word punctuation
        $handle = preg_replace('/[\'"‘’“”ʻ\[\]\(\)\{\}:]/', '', $handle);

        // Make it lowercase
        $handle = static::toLowerCase($handle);

        // Convert extended ASCII characters to basic ASCII
        $handle = static::toAscii($handle);

        // Handle must start with a letter
        $handle = preg_replace('/^[^a-z]+/', '', $handle);

        // Replace any remaining non-alphanumeric or underscore characters with spaces
        $handle = preg_replace('/[^a-z0-9_]/', ' ', $handle);

        return static::toCamelCase($handle);
    }

    /**
     * Returns a string with whitespace removed from the start and end of the
     * string. Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param string $str The string to trim.
     * @param string|null $chars String of characters to strip. Defaults to null.
     * @return string The trimmed $str.
     */
    public static function trim(string $str, ?string $chars = null): string
    {
        if ($str === '') {
            return '';
        }

        if ($chars !== null) {
            $chars = preg_quote($chars);
            $pattern = "^[{$chars}]+|[{$chars}]+\$";
        } else {
            $pattern = '^[\\s]+|[\\s]+$';
        }

        return mb_ereg_replace($pattern, '', $str);
    }

    /**
     * Returns a string with whitespace removed from the start of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param string $str The string to trim.
     * @param string|null $chars String of characters to strip. Defaults to null.
     * @return string The trimmed $str.
     * @since 3.3.0
     */
    public static function trimLeft(string $str, ?string $chars = null): string
    {
        if ($str === '') {
            return '';
        }

        if ($chars !== null) {
            $chars = preg_quote($chars);
            $pattern = "^[{$chars}]+";
        } else {
            $pattern = '^[\\s]+';
        }

        return mb_ereg_replace($pattern, '', $str);
    }

    /**
     * Returns a string with whitespace removed from the end of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param string $str The string to trim.
     * @param string|null $chars String of characters to strip. Defaults to null.
     * @return string The trimmed $str.
     * @since 3.3.0
     */
    public static function trimRight(string $str, ?string $chars = null): string
    {
        if ($str === '') {
            return '';
        }

        if ($chars !== null) {
            $chars = preg_quote($chars);
            $pattern = "[{$chars}]+$";
        } else {
            $pattern = '[\\s]+$';
        }

        return mb_ereg_replace($pattern, '', $str);
    }

    /**
     * Returns an UpperCamelCase version of the supplied string. It trims
     * surrounding spaces, capitalizes letters following digits, spaces, dashes
     * and underscores, and removes spaces, dashes, underscores.
     *
     * @param string $str The string to upper camelize.
     * @return string The upper camelized $str.
     * @since 3.3.0
     * @deprecated in 5.9.0. [[toPascalCase()]] should be used instead.
     */
    public static function upperCamelize(string $str): string
    {
        return static::toPascalCase($str);
    }

    /**
     * Converts the first character of the supplied string to uppercase.
     *
     * @param string $str The string to modify.
     * @return string The string with the first character being uppercase.
     * @since 3.3.0
     */
    public static function upperCaseFirst(string $str): string
    {
        return Str::ucfirst($str);
    }

    /**
     * Generates a valid v4 UUID string. See [http://stackoverflow.com/a/2040279/684]
     *
     * @return string The UUID.
     * @throws \Exception
     */
    public static function UUID(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            random_int(0, 0xffff), random_int(0, 0xffff),

            // 16 bits for "time_mid"
            random_int(0, 0xffff),

            // 16 bits for "time_hi_and_version", four most significant bits holds version number 4
            random_int(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low", two most significant bits holds zero and
            // one for variant DCE1.1
            random_int(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    /**
     * Converts an email from IDNA ASCII to Unicode, if the server supports IDNA ASCII strings.
     *
     * @param string $email
     * @return string
     * @since 3.5.16
     */
    public static function idnToUtf8Email(string $email): string
    {
        if (!App::supportsIdn()) {
            return $email;
        }

        if (Craft::$app->getConfig()->getGeneral()->useIdnaNontransitionalToUnicode && defined('IDNA_NONTRANSITIONAL_TO_UNICODE')) {
            $variant = IDNA_NONTRANSITIONAL_TO_UNICODE;
        } else {
            $variant = INTL_IDNA_VARIANT_UTS46;
        }
        $parts = explode('@', $email, 2);
        foreach ($parts as &$part) {
            if (!empty($part) && ($part = idn_to_utf8($part, IDNA_DEFAULT, $variant)) === false) {
                return $email;
            }
        }
        $combined = implode('@', $parts);

        // Return the original string if nothing changed besides casing
        if (strcasecmp($combined, $email) === 0) {
            return $email;
        }

        return $combined;
    }

    /**
     * Converts emoji to shortcodes.
     *
     * @param string $str
     * @return string
     * @since 4.4.3
     */
    public static function emojiToShortcodes(string $str): string
    {
        // Add delimiters around all 4-byte chars
        $dl = '__MB4_DL__';
        $dr = '__MB4_DR__';
        $str = static::replaceMb4($str, fn($char) => sprintf('%s%s%s', $dl, $char, $dr));

        // Strip out consecutive delimiters
        $str = str_replace(sprintf('%s%s', $dr, $dl), '', $str);

        // Replace all 4-byte sequences individually
        return preg_replace_callback("/$dl(.+?)$dr/", fn($m) => LitEmoji::unicodeToShortcode($m[1]), $str);
    }

    /**
     * Converts shortcodes to emoji.
     *
     * @param string $str
     * @return string
     * @since 4.4.3
     */
    public static function shortcodesToEmoji(string $str): string
    {
        return LitEmoji::shortcodeToUnicode($str);
    }

    /**
     * Escapes shortcodes.
     *
     * @param string $str
     * @return string
     * @since 4.5.0
     */
    public static function escapeShortcodes(string $str): string
    {
        $map = self::shortcodeEscapeMap();
        if ($map === false) {
            return $str;
        }
        return str_replace(array_keys($map), $map, $str);
    }

    /**
     * Unscapes shortcodes.
     *
     * @param string $str
     * @return string
     * @since 4.5.0
     */
    public static function unescapeShortcodes(string $str): string
    {
        $map = self::shortcodeEscapeMap();
        if ($map === false) {
            return $str;
        }
        return str_replace($map, array_keys($map), $str);
    }

    private static function shortcodeEscapeMap(): array|false
    {
        if (!isset(self::$_shortcodeEscapeMap)) {
            $path = Craft::$app->getPath()->getVendorPath() . '/elvanto/litemoji/src/shortcodes-array.php';
            if (file_exists($path)) {
                $shortcodes = array_keys(require $path);
                self::$_shortcodeEscapeMap = array_combine(
                    array_map(fn(string $shortcode) => ":$shortcode:", $shortcodes),
                    array_map(fn(string $shortcode) => "\\:$shortcode\\:", $shortcodes),
                );
            } else {
                Craft::warning('Unable to escape shortcodes: shortcodes-array.php doesn’t exist at the expected location.');
                self::$_shortcodeEscapeMap = false;
            }
        }

        return self::$_shortcodeEscapeMap;
    }

    /**
     * Indents each line in the given string.
     *
     * @param string $str
     * @return string
     * @since 5.2.0
     */
    public static function indent(string $str, string $indent = '    '): string
    {
        return implode("\n", array_map(fn(string $line) => $indent . $line, static::lines($str)));
    }

    /**
     * Returns a regex pattern for invisible characters.
     *
     * @return string
     * @since 5.6.1
     */
    public static function invisibleCharsRegex(): string
    {
        $invisibleCharCodes = [
            '00ad', // soft hyphen
            '0083', // no break
            '200b', // zero width space
            '200c', // zero width non-joiner
            '200d', // zero width joiner
            '200e', // LTR character
            '200f', // RTL character
            '2062', // invisible times
            '2063', // invisible comma
            '2064', // invisible plus
            'feff', //zero width non-break space
        ];

        array_walk(
            $invisibleCharCodes,
            fn(&$charCode) => $charCode = sprintf('\\x{%s}', $charCode)
        );

        return sprintf('/%s/iu', implode('|', $invisibleCharCodes));
    }
}
