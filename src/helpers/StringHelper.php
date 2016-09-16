<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use Stringy\Stringy;

/**
 * This helper class provides various multi-byte aware string related manipulation and encoding methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class StringHelper extends \yii\helpers\StringHelper
{
    // Constants
    // =========================================================================

    const UTF8 = 'UTF-8';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private static $_asciiCharMap;

    // Public Methods
    // =========================================================================

    /**
     * Returns a camelCase version of the given string. Trims surrounding spaces, capitalizes letters following digits,
     * spaces, dashes and underscores, and removes spaces, dashes, as well as underscores.
     *
     * @param string $str The string to convert to camelCase.
     *
     * @return string The string in camelCase.
     */
    public static function camelCase($str)
    {
        return (string)Stringy::create($str)->camelize();
    }

    /**
     * Returns an array consisting of the characters in the string.
     *
     * @param string $str
     *
     * @return array An array of string chars
     */
    public static function charsAsArray($str)
    {
        return (string)Stringy::create($str)->chars();
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a single space. This includes tabs and
     * newline characters, as well as multibyte whitespace such as the thin space and ideographic space.
     *
     * @param string $str The string to the whitespace from.
     *
     * @return string The trimmed string with condensed whitespace
     */
    public static function collapseWhitespace($str)
    {
        return (string)Stringy::create($str)->collapseWhitespace();
    }

    /**
     * Returns true if the string contains $needle, false otherwise. By default, the comparison is case-sensitive, but
     * can be made insensitive by setting $caseSensitive to false.
     *
     * @param string  $haystack      The string being checked.
     * @param string  $needle        The substring to look for.
     * @param boolean $caseSensitive Whether or not to force case-sensitivity.
     *
     * @return boolean Whether or not $haystack contains $needle.
     */
    public static function contains($haystack, $needle, $caseSensitive = true)
    {
        return (string)Stringy::create($haystack)->contains($needle, $caseSensitive);
    }

    /**
     * Returns true if the string contains any $needles, false otherwise. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string  $haystack      The string being checked.
     * @param array   $needles       The substrings to look for.
     * @param boolean $caseSensitive Whether or not to force case-sensitivity.
     *
     * @return boolean Whether or not $haystack contains any $needles.
     */
    public static function containsAny($haystack, $needles, $caseSensitive = true)
    {
        return (string)Stringy::create($haystack)->containsAny($needles, $caseSensitive);
    }

    /**
     * Returns true if the string contains all $needles, false otherwise. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string  $haystack      The string being checked.
     * @param array   $needles       The substrings to look for.
     * @param boolean $caseSensitive Whether or not to force case-sensitivity.
     *
     * @return boolean Whether or not $haystack contains all $needles.
     */
    public static function containsAll($haystack, $needles, $caseSensitive = true)
    {
        return (string)Stringy::create($haystack)->containsAll($needles, $caseSensitive);
    }

    /**
     * Returns the number of occurrences of $substring in the given string. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string  $str           The string to search through.
     * @param string  $substring     The substring to search for.
     * @param boolean $caseSensitive Whether or not to enforce case-sensitivity
     *
     * @return integer The number of $substring occurrences.
     */
    public static function countSubstrings($str, $substring, $caseSensitive = true)
    {
        return (string)Stringy::create($str)->countSubstr($substring, $caseSensitive);
    }

    /**
     * Returns a lowercase and trimmed string separated by the given delimiter. Delimiters are inserted before
     * uppercase characters (with the exception of the first character of the string), and in place of spaces,
     * dashes, and underscores. Alpha delimiters are not converted to lowercase.
     *
     * @param string $str       The string to delimit.
     * @param string $delimiter Sequence used to separate parts of the string
     *
     * @return string The delimited string.
     */
    public static function delimit($str, $delimiter)
    {
        return (string)Stringy::create($str)->delimit($delimiter);
    }

    /**
     * Returns true if the string ends with $substring, false otherwise. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string  $str           The string to check the end of.
     * @param string  $substring     The substring to look for.
     * @param boolean $caseSensitive Whether or not to force case-sensitivity.
     *
     * @return boolean Whether or not $str ends with $substring.
     */
    public static function endsWith($str, $substring, $caseSensitive = true)
    {
        return Stringy::create($str)->endsWith($substring, $caseSensitive);
    }

    /**
     * Ensures that a string ends with a given substring.
     *
     * @param string &$str The string to amend
     * @param string $substring The substring to look for
     * @param boolean $caseSensitive Whether or not to enforce case-sensitivity.
     *
     * @return void
     */
    public static function ensureEndsWith(&$str, $substring, $caseSensitive = true)
    {
        if (!self::endsWith($str, $substring, $caseSensitive)) {
            $str .= $substring;
        }
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's prepended.
     *
     * @param string $str       The string to modify.
     * @param string $substring The substring to add if not present.
     *
     * @return string The string prefixed by the $substring.
     */
    public static function ensureLeft($str, $substring)
    {
        return (string)Stringy::create($str)->ensureLeft($substring);
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's appended.
     *
     * @param string $str       The string to modify.
     * @param string $substring The substring to add if not present.
     *
     * @return string The string suffixed by the $substring.
     */
    public static function ensureRight($str, $substring)
    {
        return (string)Stringy::create($str)->ensureRight($substring);
    }

    /**
     * Returns the first $n characters of the string.
     *
     * @param string  $str    The string from which to get the substring.
     * @param integer $number The Number of chars to retrieve from the start.
     *
     * @return string The first $number characters.
     */
    public static function first($str, $number)
    {
        return (string)Stringy::create($str)->first($number);
    }

    /**
     * Returns the character at a specific point in a potentially multibyte string.
     *
     * @param string  $str The string to check.
     * @param integer $i   The 0-offset position in the string to check.
     *
     * @return string
     */
    public static function getCharAt($str, $i)
    {
        return (string)Stringy::create($str)->at($i);
    }

    /**
     * Returns whether the given string has any lowercase characters in it.
     *
     * @param string $str The string to check.
     *
     * @return string
     */
    public static function hasLowerCase($str)
    {
        return (string)Stringy::create($str)->hasLowerCase();
    }

    /**
     * Returns whether the given string has any uppercase characters in it.
     *
     * @param string $str The string to check.
     *
     * @return string
     */
    public static function hasUpperCase($str)
    {
        return (string)Stringy::create($str)->hasUpperCase();
    }

    /**
     * Returns the index of the first occurrence of $needle in the string, and false if not found.
     * Accepts an optional offset from which to begin the search.
     *
     * @param  string $str    The string to check the index of.
     * @param  string $needle The substring to look for.
     * @param  int    $offset The offset from which to search.
     *
     * @return int|bool The occurrence's index if found, otherwise false.
     */
    public static function indexOf($str, $needle, $offset = 0)
    {
        return (string)Stringy::create($str)->indexOf($needle, $offset);
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,and false if not found.
     * Accepts an optional offset from which to begin the search. Offsets may be negative to count from
     * the last character in the string.
     *
     * @param  string $str    The string to check the last index of.
     * @param  string $needle The substring to look for.
     * @param  int    $offset The offset from which to search.
     *
     * @return int|bool The occurrence's last index if found, otherwise false.
     */
    public static function indexOfLast($str, $needle, $offset = 0)
    {
        return (string)Stringy::create($str)->indexOfLast($needle, $offset);
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * @param string  $str       The string to insert into.
     * @param string  $substring The string to be inserted.
     * @param integer $index     The 0-based index at which to insert the substring.
     *
     * @return string The resulting string after the insertion
     */
    public static function insert($str, $substring, $index)
    {
        return (string)Stringy::create($str)->insert($substring, $index);
    }

    /**
     * Returns true if the string contains only alphabetic chars, false otherwise.
     *
     * @param string $str The string to check.
     *
     * @return boolean Whether or not $str contains only alphabetic chars.
     */
    public static function isAlpha($str)
    {
        return (string)Stringy::create($str)->isAlpha();
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars, false otherwise.
     *
     * @param string $str The string to check.
     *
     * @return boolean Whether or not $str contains only alphanumeric chars.
     */
    public static function isAlphanumeric($str)
    {
        return (string)Stringy::create($str)->isAlphanumeric();
    }

    /**
     * Returns true if the string contains only whitespace chars, false otherwise.
     *
     * @param string $str The string to check.
     *
     * @return boolean Whether or not $str contains only whitespace characters.
     */
    public static function isWhitespace($str)
    {
        return (string)Stringy::create($str)->isBlank();
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false otherwise.
     *
     * @param string $str The string to check.
     *
     * @return boolean Whether or not $str contains only hexadecimal characters
     */
    public static function isHexadecimal($str)
    {
        return (string)Stringy::create($str)->isHexadecimal();
    }

    /**
     * Returns true if the string contains only lowercase chars, false otherwise.
     *
     * @param string $str The string to check.
     *
     * @return boolean Whether or not $str contains only lowercase characters.
     */
    public static function isLowerCase($str)
    {
        return (string)Stringy::create($str)->isLowerCase();
    }

    /**
     * Returns true if the string contains only uppercase chars, false otherwise.
     *
     * @param string $str The string to check.
     *
     * @return boolean Whether or not $str contains only uppercase characters.
     */
    public static function isUpperCase($str)
    {
        return (string)Stringy::create($str)->isUpperCase();
    }

    /**
     * Returns is the given string matches a v4 UUID pattern.
     *
     * @param string $uuid The string to check.
     *
     * @return boolean Whether the string matches a v4 UUID pattern.
     */
    public static function isUUID($uuid)
    {
        return !empty($uuid) && preg_match("/[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}/uis", $uuid);
    }

    /**
     * Returns the last $number characters of the string.
     *
     * @param string  $str    The string from which to get the substring.
     * @param integer $number The Number of chars to retrieve from the end.
     *
     * @return string The last $number characters.
     */
    public static function last($str, $number)
    {
        return (string)Stringy::create($str)->last($number);
    }

    /**
     * Returns the length of the string. An alias for PHP's mb_strlen() function.
     *
     * @param string $str The string to get the length of.
     *
     * @return integer The number of characters in $str..
     */
    public static function length($str)
    {
        return (string)Stringy::create($str)->length();
    }

    /**
     * Splits on newlines and carriage returns, returning an array of strings corresponding to the lines in the string.
     *
     * @param string $str The string to split.
     *
     * @return string[] An array of strings.
     */
    public static function lines($str)
    {
        $lines = Stringy::create($str)->lines();

        foreach ($lines as $i => $line) {
            $lines[$i] = (string)$line;
        }

        return $lines;
    }

    /**
     * Converts the first character of the supplied string to lower case.
     *
     * @param string $str The string to modify.
     *
     * @return string The string with the first character converted to lowercase.
     */
    public static function lowercaseFirst($str)
    {
        return (string)Stringy::create($str)->lowerCaseFirst();
    }

    /**
     * kebab-cases a string.
     *
     * @param string  $string            The string
     * @param string  $glue              The string used to glue the words together (default is a hyphen)
     * @param boolean $lower             Whether the string should be lowercased (default is true)
     * @param boolean $removePunctuation Whether punctuation marks should be removed (default is true)
     *
     * @return string The kebab-cased string
     *
     * @see toCamelCase()
     * @see toPascalCase()
     * @see toSnakeCase()
     */
    public static function toKebabCase($string, $glue = '-', $lower = true, $removePunctuation = true)
    {
        $words = self::_prepStringForCasing($string, $lower, $removePunctuation);
        $string = implode($glue, $words);

        return $string;
    }

    /**
     * camelCases a string.
     *
     * @param string $string The string
     *
     * @return string
     *
     * @see toKebabCase()
     * @see toPascalCase()
     * @see toSnakeCase()
     */
    public static function toCamelCase($string)
    {
        $words = self::_prepStringForCasing($string);

        if (!$words) {
            return '';
        }

        $string = array_shift($words).implode('', array_map([
                get_called_class(),
                'uppercaseFirst'
            ], $words));

        return $string;
    }

    /**
     * PascalCases a string.
     *
     * @param string $string The string
     *
     * @return string
     *
     * @see toKebabCase()
     * @see toCamelCase()
     * @see toSnakeCase()
     */
    public static function toPascalCase($string)
    {
        $words = self::_prepStringForCasing($string);
        $string = implode('', array_map([
            get_called_class(),
            'uppercaseFirst'
        ], $words));

        return $string;
    }

    /**
     * snake_cases a string.
     *
     * @param string $string The string
     *
     * @return string
     *
     * @see toKebabCase()
     * @see toCamelCase()
     * @see toPascalCase()
     */
    public static function toSnakeCase($string)
    {
        $words = self::_prepStringForCasing($string);
        $string = implode('_', $words);

        return $string;
    }

    /**
     * Splits a string into an array of the words in the string.
     *
     * @param string $string The string
     *
     * @return string[] The words in the string
     */
    public static function splitOnWords($string)
    {
        // Split on anything that is not alphanumeric, or a period, underscore, or hyphen.
        // Reference: http://www.regular-expressions.info/unicode.html
        preg_match_all('/[\p{L}\p{N}\p{M}\._-]+/u', $string, $matches);

        return ArrayHelper::filterEmptyStringsFromArray($matches[0]);
    }

    /**
     * Strips HTML tags out of a given string.
     *
     * @param string $str The string.
     *
     * @return string The string, sans-HTML
     */
    public static function stripHtml($str)
    {
        return preg_replace('/<(.*?)>/u', '', $str);
    }

    /**
     * Returns a new string of a given length such that both sides of the string are padded.
     *
     * @param  string $str    The string to pad.
     * @param  int    $length The desired string length after padding.
     * @param  string $padStr The string used to pad, defaults to space.
     *
     * @return string The padded string.
     */
    public static function padBoth($str, $length, $padStr = ' ')
    {
        return (string)Stringy::create($str)->padBoth($length, $padStr);
    }

    /**
     * Returns a new string of a given length such that the beginning of the string is padded.
     *
     * @param string  $str    The string to pad.
     * @param integer $length The desired string length after padding.
     * @param string  $padStr The string used to pad, defaults to space.
     *
     * @return string The padded string.
     */
    public static function padLeft($str, $length, $padStr = ' ')
    {
        return (string)Stringy::create($str)->padLeft($length, $padStr);
    }

    /**
     * Returns a new string of a given length such that the end of the string is padded.
     *
     * @param string  $str    The string to pad.
     * @param integer $length The desired string length after padding.
     * @param string  $padStr The string used to pad, defaults to space.
     *
     * @return string The padded string.
     */
    public static function padRight($str, $length, $padStr = ' ')
    {
        return (string)Stringy::create($str)->padRight($length, $padStr);
    }

    /**
     * Generates a random string of latin alphanumeric characters that defaults to a $length of 36. If $extendedChars is
     * set to true, additional symbols can be included in the string.  Note that the generated string is *not* a
     * cryptographically secure string. If you need a cryptographically secure string, see
     * [[Craft::$app->security->randomString]].
     *
     * @param integer $length        The length of the random string. Defaults to 36.
     * @param boolean $extendedChars Whether to include symbols in the random string.
     *
     * @return string The randomly generated string.
     */
    public static function randomString($length = 36, $extendedChars = false)
    {
        if ($extendedChars) {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"';
        } else {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        }

        return static::randomStringWithChars($validChars, $length);
    }

    /**
     * Generates a random string of characters. Note that the generated string is *not* a
     * cryptographically secure string. If you need a cryptographically secure string, see
     * [[Craft::$app->security->randomString]].
     *
     * @param string  $validChars A string containing the valid characters
     * @param integer $length     The length of the random string
     *
     * @return string The randomly generated string.
     */
    public static function randomStringWithChars($validChars, $length)
    {
        $randomString = '';

        // count the number of chars in the valid chars string so we know how many choices we have
        $numValidChars = static::length($validChars);

        // repeat the steps until we've created a string of the right length
        for ($i = 0; $i < $length; $i++) {
            // pick a random number from 1 up to the number of valid chars
            $randomPick = mt_rand(1, $numValidChars);

            // take the random character out of the string of valid chars
            $randomChar = $validChars[$randomPick - 1];

            // add the randomly-chosen char onto the end of our string
            $randomString .= $randomChar;
        }

        return $randomString;
    }

    /**
     * Replaces all occurrences of $pattern in $str by $replacement. An alias for mb_ereg_replace().
     *
     * @param string $str         The haystack to search through.
     * @param string $pattern     The regular expression pattern.
     * @param string $replacement The string to replace with.
     * @param string $options     Matching conditions to be used. Defaults to 'msr'. See
     *                            [here](http://php.net/manual/en/function.mb-ereg-replace.php) for all options.
     *
     * @return string The resulting string after the replacements.
     */
    public static function regexReplace($str, $pattern, $replacement, $options = 'msr')
    {
        return (string)Stringy::create($str)->regexReplace($pattern, $replacement, $options);
    }

    /**
     * Returns a new string with the prefix $substring removed, if present.
     *
     * @param string $str       The string from which to remove the prefix.
     * @param string $substring The prefix to remove.
     *
     * @return string The string without the prefix $substring.
     */
    public static function removeLeft($str, $substring)
    {
        return (string)Stringy::create($str)->removeLeft($substring);
    }

    /**
     * Returns a new string with the suffix $substring removed, if present.
     *
     * @param string $str       The string from which to remove the suffix.
     * @param string $substring The suffix to remove.
     *
     * @return string The string without the suffix $substring.
     */
    public static function removeRight($str, $substring)
    {
        return (string)Stringy::create($str)->removeLeft($substring);
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * @param string $str         The haystack to search through.
     * @param string $search      The needle to search for.
     * @param string $replacement The string to replace with.
     *
     * @return string The resulting string after the replacements.
     */
    public static function replace($str, $search, $replacement)
    {
        return (string)Stringy::create($str)->replace($search, $replacement);
    }

    /**
     * Returns a reversed string. A multibyte version of strrev().
     *
     * @param string $str The string to reverse.
     *
     * @return string The reversed string.
     */
    public static function reverse($str)
    {
        return (string)Stringy::create($str)->reverse();
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not split words. If $substring is provided,
     * and truncating occurs, the string is further truncated so that the substring may be appended without exceeding t
     * he desired length.
     *
     * @param string  $str       The string to truncate.
     * @param integer $length    The desired length of the truncated string.
     * @param string  $substring The substring to append if it can fit.
     *
     * @return string The resulting string after truncating.
     */
    public static function safeTruncate($str, $length, $substring = '')
    {
        return (string)Stringy::create($str)->safeTruncate($length, $substring);
    }

    /**
     * Returns true if the string begins with $substring, false otherwise. By default, the comparison is case-sensitive,
     * but can be made insensitive by setting $caseSensitive to false.
     *
     * @param string  $str           The string to check the start of.
     * @param string  $substring     The substring to look for.
     * @param boolean $caseSensitive Whether or not to enforce case-sensitivity.
     *
     * @return boolean Whether or not $str starts with $substring.
     */
    public static function startsWith($str, $substring, $caseSensitive = true)
    {
        return Stringy::create($str)->startsWith($substring, $caseSensitive);
    }

    /**
     * Ensures that a string starts with a given substring.
     *
     * @param string &$str The string to amend
     * @param string $substring The substring to look for
     * @param boolean $caseSensitive Whether or not to enforce case-sensitivity.
     *
     * @return void
     */
    public static function ensureStartsWith(&$str, $substring, $caseSensitive = true)
    {
        if (!self::startsWith($str, $substring, $caseSensitive)) {
            $str = $substring.$str;
        }
    }

    /**
     * Returns the substring beginning at $start with the specified $length. It differs from the mb_substr() function in
     * that providing a $length of null will return the rest of the string, rather than an empty string.
     *
     * @param string  $str    The string to get the length of.
     * @param integer $start  Position of the first character to use.
     * @param integer $length Maximum number of characters used.
     *
     * @return string The substring of $str.
     */
    public static function substr($str, $start, $length = null)
    {
        return (string)Stringy::create($str)->substr($start, $length);
    }

    /**
     * Returns a case swapped version of the string.
     *
     * @param string $str The string to swap case.
     *
     * @return string The string with each character's case swapped.
     */
    public static function swapCase($str)
    {
        return (string)Stringy::create($str)->swapCase();
    }

    /**
     * Returns a trimmed string with the first letter of each word capitalized. Ignores the case of other letters,
     * preserving any acronyms. Also accepts an array, $ignore, allowing you to list words not to be capitalized.
     *
     * @param string $str    The string to titleize.
     * @param array  $ignore An array of words not to capitalize.
     *
     * @return string The titleized string.
     */
    public static function titleize($str, $ignore = null)
    {
        return (string)Stringy::create($str)->titleize($ignore);
    }

    /**
     * Converts all characters in the string to lowercase. An alias for PHP's mb_strtolower().
     *
     * @param string $str The string to convert to lowercase.
     *
     * @return string The lowercase string.
     */
    public static function toLowerCase($str)
    {
        return (string)Stringy::create($str)->toLowerCase();
    }

    /**
     * Converts an object to its string representation. If the object is an array, will glue the array elements togeter
     * with the $glue param. Otherwise will cast the object to a string.
     *
     * @param mixed  $object The object to convert to a string.
     * @param string $glue   The glue to use if the object is an array.
     *
     * @return string The string representation of the object.
     */
    public static function toString($object, $glue = ',')
    {
        if (is_array($object) || $object instanceof \IteratorAggregate) {
            $stringValues = [];

            foreach ($object as $value) {
                $stringValues[] = static::toString($value, $glue);
            }

            return implode($glue, $stringValues);
        }

        return (string)$object;
    }

    /**
     * Converts the first character of each word in the string to uppercase.
     *
     * @param string $str The string to convert case.
     *
     * @return string The title-cased string.
     */
    public static function toTitleCase($str)
    {
        return (string)Stringy::create($str)->toTitleCase();
    }

    /**
     * Converts all characters in the string to uppercase. An alias for PHP's mb_strtoupper().
     *
     * @param string $str The string to convert to uppercase.
     *
     * @return string The uppercase string.
     */
    public static function toUpperCase($str)
    {
        return (string)Stringy::create($str)->toUpperCase();
    }

    /**
     * Returns the trimmed string. An alias for PHP's trim() function.
     *
     * @param string $str The string to trim.
     *
     * @return string The trimmed $str.
     */
    public static function trim($str)
    {
        return (string)Stringy::create($str)->trim();
    }

    /**
     * Converts the first character of the supplied string to uppercase.
     *
     * @param string $str The string to modify.
     *
     * @return string The string with the first character being uppercase.
     */
    public static function uppercaseFirst($str)
    {
        return (string)Stringy::create($str)->upperCaseFirst();
    }

    /**
     * Generates a valid v4 UUID string. See [http://stackoverflow.com/a/2040279/684]
     *
     * @return string The UUID.
     */
    public static function UUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version", four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low", two most significant bits holds zero and
            // one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Returns ASCII character mappings, merging in any custom defined mappings from the 'customAsciiCharMappings'
     * config setting.
     *
     * @return array The fully merged ASCII character mappings.
     */
    public static function getAsciiCharMap()
    {
        if (!isset(static::$_asciiCharMap)) {
            // Get the map from Stringy.
            static::$_asciiCharMap = (new \craft\app\helpers\Stringy(''))->getAsciiCharMap();

            foreach (Craft::$app->getConfig()->get('customAsciiCharMappings') as $asciiChar => $values) {
                static::$_asciiCharMap[$asciiChar] = $values;
            }
        }

        return static::$_asciiCharMap;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are replaced with their closest ASCII
     * counterparts, and the rest are removed.
     *
     * @param string $str The string to convert.
     *
     * @return string The string that contains only ASCII characters.
     */
    public static function toAscii($str)
    {
        return (string)Stringy::create($str)->toAscii();
    }

    // Encodings
    // -----------------------------------------------------------------------

    /**
     * Attempts to convert a string to UTF-8 and clean any non-valid UTF-8 characters.
     *
     * @param string $string
     *
     * @return boolean|string
     */
    public static function convertToUtf8($string)
    {
        // If it's already a UTF8 string, just clean and return it
        if (static::isUtf8($string)) {
            return HtmlPurifier::cleanUtf8($string);
        }

        // Otherwise set HTMLPurifier to the actual string encoding
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', (string)static::getEncoding($string));

        // Clean it
        $string = HtmlPurifier::cleanUtf8($string);

        // Convert it to UTF8 if possible
        if (App::checkForValidIconv()) {
            $string = HtmlPurifier::convertToUtf8($string, $config);
        } else {
            $encoding = static::getEncoding($string);
            $string = mb_convert_encoding($string, 'utf-8', $encoding);
        }

        return $string;
    }

    /**
     * Checks if the given string is UTF-8 encoded.
     *
     * @param string $string The string to check.
     *
     * @return boolean
     */
    public static function isUtf8($string)
    {
        return static::getEncoding($string) == 'utf-8' ? true : false;
    }

    /**
     * Gets the current encoding of the given string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function getEncoding($string)
    {
        return static::toLowerCase(mb_detect_encoding($string, mb_detect_order(), true));
    }

    /**
     * HTML-encodes any 4-byte UTF-8 characters.
     *
     * @param string $string The string
     *
     * @return string The string with converted 4-byte UTF-8 characters
     *
     * @see http://stackoverflow.com/a/16496730/1688568
     */
    public static function encodeMb4($string)
    {
        // Does this string have any 4+ byte Unicode chars?
        if (max(array_map('ord', str_split($string))) >= 240) {
            $string = preg_replace_callback('/./u', function (array $match) {
                if (strlen($match[0]) >= 4) {
                    // (Logic pulled from WP's wp_encode_emoji() function)
                    // UTF-32's hex encoding is the same as HTML's hex encoding.
                    // So, by converting from UTF-8 to UTF-32, we magically
                    // get the correct hex encoding.
                    $unpacked = unpack('H*', mb_convert_encoding($match[0], 'UTF-32', 'UTF-8'));

                    return isset($unpacked[1]) ? '&#x'.ltrim($unpacked[1], '0').';' : '';
                }

                return $match[0];
            }, $string);
        }

        return $string;
    }

    /**
     * Prepares a string for casing routines.
     *
     * @param string  $string            The string
     * @param boolean $lower
     * @param boolean $removePunctuation Whether punctuation marks should be removed (default is true)
     *
     * @return array The prepped words in the string
     *
     * @see toKebabCase()
     * @see toCamelCase()
     * @see toPascalCase()
     * @see toSnakeCase()
     */
    private static function _prepStringForCasing($string, $lower = true, $removePunctuation = true)
    {
        if ($lower) {
            // Make it lowercase
            $string = static::toLowerCase($string);
        }

        if ($removePunctuation) {
            $string = str_replace(['.', '_', '-'], ' ', $string);
        }

        // Remove inner-word punctuation.
        $string = preg_replace('/[\'"‘’“”\[\]\(\)\{\}:]/u', '', $string);

        // Split on the words and return
        return static::splitOnWords($string);
    }
}
