<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use Stringy\StaticStringy;

/**
 * This helper class provides various multi-byte aware string related manipulation and encoding methods.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		return StaticStringy::camelize($str);
	}

	/**
	 * Returns an array consisting of the characters in the string.
	 *
	 * @return array An array of string chars
	 */
	public static function charsAsArray($str)
	{
		return StaticStringy::chars($str);
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
		return StaticStringy::collapseWhitespace($str);
	}

	/**
	 * Returns true if the string contains $needle, false otherwise. By default, the comparison is case-sensitive, but
	 * can be made insensitive by setting $caseSensitive to false.
	 *
	 * @param string $haystack      The string being checked.
	 * @param string $needle        The substring to look for.
	 * @param bool   $caseSensitive Whether or not to force case-sensitivity.
	 *
	 * @return bool Whether or not $haystack contains $needle.
	 */
	public static function contains($haystack, $needle, $caseSensitive = true)
	{
		return StaticStringy::contains($haystack, $needle, $caseSensitive);
	}

	/**
	 * Returns true if the string contains any $needles, false otherwise. By default, the comparison is case-sensitive,
	 * but can be made insensitive by setting $caseSensitive to false.
	 *
	 * @param string $haystack      The string being checked.
	 * @param array  $needles       The substrings to look for.
	 * @param bool   $caseSensitive Whether or not to force case-sensitivity.
	 *
	* @return bool Whether or not $haystack contains any $needles.
	 */
	public static function containsAny($haystack, $needles, $caseSensitive = true)
	{
		return StaticStringy::containsAny($haystack, $needles, $caseSensitive);
	}

	/**
	 * Returns true if the string contains all $needles, false otherwise. By default, the comparison is case-sensitive,
	 * but can be made insensitive by setting $caseSensitive to false.
	 *
	 * @param string $haystack      The string being checked.
	 * @param array  $needles       The substrings to look for.
	 * @param bool   $caseSensitive Whether or not to force case-sensitivity.
	 *
	 * @return bool Whether or not $haystack contains all $needles.
	 */
	public static function containsAll($haystack, $needles, $caseSensitive = true)
	{
		return StaticStringy::containsAll($haystack, $needles, $caseSensitive);
	}

	/**
	 * Returns the number of occurrences of $substring in the given string. By default, the comparison is case-sensitive,
	 * but can be made insensitive by setting $caseSensitive to false.
	 *
	 * @param string $str           The string to search through.
	 * @param string $substring     The substring to search for.
	 * @param bool   $caseSensitive Whether or not to enforce case-sensitivity
	 *
	 * @return int The number of $substring occurrences.
	 */
	public static function countSubstrings($str, $substring, $caseSensitive = true)
	{
		return StaticStringy::countSubstr($str, $substring, $caseSensitive);
	}

	/**
	 * Returns true if the string ends with $substring, false otherwise. By default, the comparison is case-sensitive,
	 * but can be made insensitive by setting $caseSensitive to false.
	 *
	 * @param string $str           The string to check the end of.
	 * @param string $substring     The substring to look for.
	 * @param bool   $caseSensitive Whether or not to force case-sensitivity.
	 *
	 * @return bool Whether or not $str ends with $substring.
	 */
	public static function endsWith($str, $substring, $caseSensitive = true)
	{
		return StaticStringy::endsWith($str, $substring, $caseSensitive);
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
		return StaticStringy::ensureLeft($str, $substring);
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
		return StaticStringy::ensureRight($str, $substring);
	}

	/**
	 * Returns the first $n characters of the string.
	 *
	 * @param string $str    The string from which to get the substring.
	 * @param int    $number The Number of chars to retrieve from the start.
	 *
	 * @return string The first $number characters.
	 */
	public static function first($str, $number)
	{
		return StaticStringy::first($str, $number);
	}

	/**
	 * Returns the character at a specific point in a potentially multibyte string.
	 *
	 * @param string $str The string to check.
	 * @param int    $i   The 0-offset position in the string to check.
	 *
	 * @return string
	 */
	public static function getCharAt($str, $i)
	{
		return StaticStringy::at($str, $i);
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
		return StaticStringy::hasLowerCase($str);
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
		return StaticStringy::hasUpperCase($str);
	}

	/**
	 * Inserts $substring into the string at the $index provided.
	 *
	 * @param string $str       The string to insert into.
	 * @param string $substring The string to be inserted.
	 * @param int    $index     The 0-based index at which to insert the substring.
	 *
	 * @return string The resulting string after the insertion
	 */
	public static function insert($str, $substring, $index)
	{
		return StaticStringy::insert($str, $substring, $index);
	}

	/**
	 * Returns true if the string contains only alphabetic chars, false otherwise.
	 *
	 * @param string $str The string to check.
	 *
	 * @return bool Whether or not $str contains only alphabetic chars.
	 */
	public static function isAlpha($str)
	{
		return StaticStringy::isAlpha($str);
	}

	/**
	 * Returns true if the string contains only alphabetic and numeric chars, false otherwise.
	 *
	 * @param string $str The string to check.
	 *
	 * @return bool Whether or not $str contains only alphanumeric chars.
	 */
	public static function isAlphanumeric($str)
	{
		return StaticStringy::isAlphanumeric($str);
	}

	/**
	 * Returns true if the string contains only whitespace chars, false otherwise.
	 *
	 * @param string $str The string to check.
	 *
	 * @return bool Whether or not $str contains only whitespace characters.
	 */
	public static function isWhitespace($str)
	{
		return StaticStringy::isBlank($str);
	}

	/**
	 * Returns true if the string contains only hexadecimal chars, false otherwise.
	 *
	 * @param string $str The string to check.
	 *
	 * @return bool Whether or not $str contains only hexadecimal characters
	 */
	public static function isHexadecimal($str)
	{
		return StaticStringy::isHexadecimal($str);
	}

	/**
	 * Returns true if the string contains only lowercase chars, false otherwise.
	 *
	 * @param string $str The string to check.
	 *
	 * @return bool Whether or not $str contains only lowercase characters.
	 */
	public static function isLowerCase($str)
	{
		return StaticStringy::isLowerCase($str);
	}

	/**
	 * Returns true if the string contains only uppercase chars, false otherwise.
	 *
	 * @param string $str The string to check.
	 *
	 * @return bool Whether or not $str contains only uppercase characters.
	 */
	public static function isUpperCase($str)
	{
		return StaticStringy::isUpperCase($str);
	}

	/**
	 * Returns is the given string matches a v4 UUID pattern.
	 *
	 * @param string $uuid The string to check.
	 *
	 * @return bool Whether the string matches a v4 UUID pattern.
	 */
	public static function isUUID($uuid)
	{
		return !empty($uuid) && preg_match("/[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}/uis", $uuid);
	}

	/**
	 * Returns the last $number characters of the string.
	 *
	 * @param string $str    The string from which to get the substring.
	 * @param int    $number The Number of chars to retrieve from the end.
	 *
	 * @return string The last $number characters.
	 */
	public static function last($str, $number)
	{
		return StaticStringy::last($str, $number);
	}

	/**
	 * Returns the length of the string. An alias for PHP's mb_strlen() function.
	 *
	 * @param string $str The string to get the length of.
	 *
	 * @return int The number of characters in $str..
	 */
	public static function length($str)
	{
		return StaticStringy::length($str);
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
		return StaticStringy::lowerCaseFirst($str);
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
		return StaticStringy::padBoth($str, $length, $padStr);
	}

	/**
	 * Returns a new string of a given length such that the beginning of the string is padded.
	 *
	 * @param string $str    The string to pad.
	 * @param int    $length The desired string length after padding.
	 * @param string $padStr The string used to pad, defaults to space.
	 *
	 * @return string The padded string.
	 */
	public static function padLeft($str, $length, $padStr = ' ')
	{
		return StaticStringy::padLeft($str, $length, $padStr);
	}

	/**
	 * Returns a new string of a given length such that the end of the string is padded.
	 *
	 * @param string $str     The string to pad.
	 * @param int    $length  The desired string length after padding.
	 * @param string $padStr  The string used to pad, defaults to space.
	 *
	 * @return string The padded string.
	 */
	public static function padRight($str, $length, $padStr = ' ')
	{
		return StaticStringy::padRight($str, $length, $padStr);
	}

	/**
	 * Generates a random string of latin alphanumeric characters that defaults to a $length of 36. If $extendedChars is
	 * set to true, additional symbols can be included in the string.  Note that the generated string is *not* a
	 * cryptographically secure string. If you need a cryptographically secure string, see
	 * [[Craft::$app->security->randomString]].
	 *
	 * @param int  $length        The length of the random string. Defaults to 36.
	 * @param bool $extendedChars Whether to include symbols in the random string.
	 *
	 * @return string The randomly generated string.
	 */
	public static function randomString($length = 36, $extendedChars = false)
	{
		if ($extendedChars)
		{
			$validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"';
		}
		else
		{
			$validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		}

		$randomString = '';

		// count the number of chars in the valid chars string so we know how many choices we have
		$numValidChars = static::length($validChars);

		// repeat the steps until we've created a string of the right length
		for ($i = 0; $i < $length; $i++)
		{
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
		return StaticStringy::regexReplace($str, $pattern, $replacement, $options);
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
		return StaticStringy::removeLeft($str, $substring);
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
		return StaticStringy::removeLeft($str, $substring);
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
		return StaticStringy::replace($str, $search, $replacement);
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
		return StaticStringy::reverse($str);
	}

	/**
	 * Truncates the string to a given length, while ensuring that it does not split words. If $substring is provided,
	 * and truncating occurs, the string is further truncated so that the substring may be appended without exceeding t
	 * he desired length.
	 *
	 * @param string $str       The string to truncate.
	 * @param int    $length    The desired length of the truncated string.
	 * @param string $substring The substring to append if it can fit.
	 *
	 * @return string The resulting string after truncating.
	 */
	public static function safeTruncate($str, $length, $substring = '')
	{
		return StaticStringy::safeTruncate($str, $length, $substring);
	}

	/**
	 * Returns true if the string begins with $substring, false otherwise. By default, the comparison is case-sensitive,
	 * but can be made insensitive by setting $caseSensitive to false.
	 *
	 * @param string $str           The string to check the start of.
	 * @param string $substring     The substring to look for.
	 * @param bool   $caseSensitive Whether or not to enforce case-sensitivity.
	 *
	 * @return bool Whether or not $str starts with $substring.
	 */
	public static function startsWith($str, $substring, $caseSensitive = true)
	{
		return StaticStringy::startsWith($str, $substring, $caseSensitive);
	}

	/**
	 * Returns the substring beginning at $start with the specified $length. It differs from the mb_substr() function in
	 * that providing a $length of null will return the rest of the string, rather than an empty string.
	 *
	 * @param string $str    The string to get the length of.
	 * @param int    $start  Position of the first character to use.
	 * @param int    $length Maximum number of characters used.
	 *
	 * @return string The substring of $str.
	 */
	public static function substr($str, $start, $length = null)
	{
		return StaticStringy::substr($str, $start, $length);
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
		return StaticStringy::swapCase($str);
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
		return StaticStringy::titleize($str, $ignore);
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
		return StaticStringy::toLowerCase($str);
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
		if (is_array($object) || $object instanceof \IteratorAggregate)
		{
			$stringValues = [];

			foreach ($object as $value)
			{
				$stringValues[] = static::toString($value, $glue);
			}

			return implode($glue, $stringValues);
		}
		else
		{
			return (string) $object;
		}
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
		return StaticStringy::toLowerCase($str);
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
		return StaticStringy::toUpperCase($str);
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
		return StaticStringy::trim($str);
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
		return StaticStringy::upperCaseFirst($str);
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
		if (!isset(static::$_asciiCharMap))
		{
			// Get the map from Stringy.
			static::$_asciiCharMap = (new Stringy(''))->getAsciiCharMap();

			foreach (Craft::$app->getConfig()->get('customAsciiCharMappings') as $asciiChar => $values)
			{
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
		return StaticStringy::toAscii($str);
	}

	// Encodings
	// -----------------------------------------------------------------------

	/**
	 * Attempts to convert a string to UTF-8 and clean any non-valid UTF-8 characters.
	 *
	 * @param string $string
	 *
	 * @return bool|string
	 */
	public static function convertToUtf8($string)
	{
		// If it's already a UTF8 string, just clean and return it
		if (static::isUtf8($string))
		{
			return HtmlPurifier::cleanUtf8($string);
		}

		// Otherwise set HTMLPurifier to the actual string encoding
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('Core.Encoding', static::getEncoding($string));

		// Clean it
		$string = HtmlPurifier::cleanUtf8($string);

		// Convert it to UTF8 if possible
		if (AppHelper::checkForValidIconv())
		{
			$string = HtmlPurifier::convertToUtf8($string, $config);
		}
		else
		{
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
	 * @return bool
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
}
