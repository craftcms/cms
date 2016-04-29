<?php
namespace Craft;

/**
 * Class StringHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class StringHelper
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

	/**
	 * @var
	 */
	private static $_asciiPunctuation;

	/**
	 * @var
	 */
	private static $_iconv;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the character at a specific point in a potentially multibyte string.
	 *
	 * @param  string $str
	 * @param  int    $i
	 *
	 * @see http://stackoverflow.com/questions/10360764/there-are-simple-way-to-get-a-character-from-multibyte-string-in-php
	 * @return string
	 */
	public static function getCharAt($str, $i)
	{
		return mb_substr($str, $i, 1);
	}

	/**
	 * Converts an array to a string.
	 *
	 * @param mixed  $arr
	 * @param string $glue
	 *
	 * @return string
	 */
	public static function arrayToString($arr, $glue = ',')
	{
		if (is_array($arr) || $arr instanceof \IteratorAggregate)
		{
			$stringValues = array();

			foreach ($arr as $value)
			{
				$stringValues[] = static::arrayToString($value, $glue);
			}

			return implode($glue, $stringValues);
		}
		else
		{
			return (string) $arr;
		}
	}

	/**
	 * @param $value
	 *
	 * @throws Exception
	 * @return bool
	 */
	public static function isNullOrEmpty($value)
	{
		if ($value === null || $value === '')
		{
			return true;
		}

		if (!is_string($value))
		{
			throw new Exception(Craft::t('IsNullOrEmpty requires a string.'));
		}

		return false;
	}

	/**
	 * @param $value
	 *
	 * @throws Exception
	 * @return bool
	 */
	public static function isNotNullOrEmpty($value)
	{
		return !static::isNullOrEmpty($value);
	}

	/**
	 * @param int  $length
	 * @param bool $extendedChars
	 *
	 * @return string
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
		$numValidChars = mb_strlen($validChars);

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
	 * @return string
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
	 * Returns is the given string matches a UUID pattern.
	 *
	 * @param $uuid
	 *
	 * @return bool
	 */
	public static function isUUID($uuid)
	{
		return !empty($uuid) && preg_match("/[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}/uis", $uuid);
	}

	/**
	 * @param $string
	 *
	 * @return mixed
	 */
	public static function escapeRegexChars($string)
	{
		$charsToEscape = str_split("\\/^$.,{}[]()|<>:*+-=");
		$escapedChars = array();

		foreach ($charsToEscape as $char)
		{
			$escapedChars[] = "\\".$char;
		}

		return  str_replace($charsToEscape, $escapedChars, $string);
	}

	/**
	 * Returns ASCII character mappings.
	 *
	 * @return array
	 */
	public static function getAsciiCharMap()
	{
		if (!isset(static::$_asciiCharMap))
		{
			static::$_asciiCharMap = array(
				216 => 'O',  223 => 'ss', 224 => 'a',  225 => 'a',  226 => 'a',
				229 => 'a',  227 => 'ae', 230 => 'ae', 228 => 'ae', 231 => 'c',
				232 => 'e',  233 => 'e',  234 => 'e',  235 => 'e',  236 => 'i',
				237 => 'i',  238 => 'i',  239 => 'i',  241 => 'n',  242 => 'o',
				243 => 'o',  244 => 'o',  245 => 'o',  246 => 'oe', 248 => 'o',
				249 => 'u',  250 => 'u',  251 => 'u',  252 => 'ue', 255 => 'y',
				257 => 'aa', 269 => 'ch', 275 => 'ee', 291 => 'gj', 299 => 'ii',
				311 => 'kj', 316 => 'lj', 326 => 'nj', 353 => 'sh', 363 => 'uu',
				382 => 'zh', 256 => 'aa', 268 => 'ch', 274 => 'ee', 290 => 'gj',
				298 => 'ii', 310 => 'kj', 315 => 'lj', 325 => 'nj', 337 => 'o',
				352 => 'sh', 362 => 'uu', 369 => 'u',  381 => 'zh', 260 => 'A',
				261 => 'a',  262 => 'C',  263 => 'c',  280 => 'E',  281 => 'e',
				321 => 'L',  322 => 'l',  323 => 'N',  324 => 'n',  211 => 'O',
				346 => 'S',  347 => 's',  377 => 'Z',  378 => 'z',  379 => 'Z',
				380 => 'z',  388 => 'z',
			);

			foreach (craft()->config->get('customAsciiCharMappings') as $ascii => $char)
			{
				static::$_asciiCharMap[$ascii] = $char;
			}
		}

		return static::$_asciiCharMap;
	}

	/**
	 * Returns the asciiPunctuation array.
	 *
	 * @return array
	 */
	public static function getAsciiPunctuation()
	{
		if (!isset(static::$_asciiPunctuation))
		{
			static::$_asciiPunctuation =  array(
				33, 34, 35, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 58, 59, 60, 62, 63,
				64, 91, 92, 93, 94, 123, 124, 125, 126, 161, 162, 163, 164, 165, 166,
				167, 168, 169, 170, 171, 172, 174, 175, 176, 177, 178, 179, 180, 181,
				182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 215, 402, 710, 732,
				8211, 8212, 8213, 8216, 8217, 8218, 8220, 8221, 8222, 8224, 8225, 8226,
				8227, 8230, 8240, 8242, 8243, 8249, 8250, 8252, 8254, 8260, 8364, 8482,
				8592, 8593, 8594, 8595, 8596, 8629, 8656, 8657, 8658, 8659, 8660, 8704,
				8706, 8707, 8709, 8711, 8712, 8713, 8715, 8719, 8721, 8722, 8727, 8730,
				8733, 8734, 8736, 8743, 8744, 8745, 8746, 8747, 8756, 8764, 8773, 8776,
				8800, 8801, 8804, 8805, 8834, 8835, 8836, 8838, 8839, 8853, 8855, 8869,
				8901, 8968, 8969, 8970, 8971, 9001, 9002, 9674, 9824, 9827, 9829, 9830
			);
		}

		return static::$_asciiPunctuation;
	}

	/**
	 * Converts extended ASCII characters to ASCII.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function asciiString($str)
	{
		$asciiStr = '';
		$strlen = strlen($str);
		$asciiCharMap = static::getAsciiCharMap();

		// Code adapted from http://php.net/ord#109812
		$offset = 0;

		while ($offset < $strlen)
		{
			// ord() doesn't support UTF-8 so we need to do some extra work to determine the ASCII code
			$ascii = ord(substr($str, $offset, 1));

			if ($ascii >= 128) // otherwise 0xxxxxxx
			{
				if ($ascii < 224)
				{
					$bytesnumber = 2; // 110xxxxx
				}
				else if ($ascii < 240)
				{
					$bytesnumber = 3; // 1110xxxx
				}
				else if ($ascii < 248)
				{
					$bytesnumber = 4; // 11110xxx
				}

				$tempAscii = $ascii - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);

				for ($i = 2; $i <= $bytesnumber; $i++)
				{
					$offset++;
					$ascii2 = ord(substr($str, $offset, 1)) - 128; // 10xxxxxx
					$tempAscii = $tempAscii * 64 + $ascii2;
				}

				$ascii = $tempAscii;
			}

			$offset++;

			// Is this an ASCII character?
			if ($ascii >= 32 && $ascii < 128)
			{
				$asciiStr .= chr($ascii);
			}
			// Do we have an ASCII mapping for it?
			else if (isset($asciiCharMap[$ascii]))
			{
				$asciiStr .= $asciiCharMap[$ascii];
			}
		}

		return $asciiStr;
	}

	/**
	 * Normalizes search keywords.
	 *
	 * @param string $str    The dirty keywords.
	 * @param array  $ignore Ignore words to strip out.
	 *
	 * @return string The cleansed keywords.
	 */
	public static function normalizeKeywords($str, $ignore = array())
	{
		// Flatten
		if (is_array($str)) $str = static::arrayToString($str, ' ');

		// Get rid of tags
		$str = strip_tags($str);

		// Convert non-breaking spaces entities to regular ones
		$str = str_replace(array('&nbsp;', '&#160;', '&#xa0;') , ' ', $str);

		// Get rid of entities
		$str = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $str);

		// Remove punctuation and diacritics
		$str = strtr($str, static::_getCharMap());

		// Normalize to lowercase
		$str = StringHelper::toLowerCase($str);

		// Remove ignore-words?
		if (is_array($ignore) && ! empty($ignore))
		{
			foreach ($ignore as $word)
			{
				$word = preg_quote(static::_normalizeKeywords($word));
				$str  = preg_replace("/\b{$word}\b/u", '', $str);
			}
		}

		// Strip out new lines and superfluous spaces
		$str = preg_replace('/[\n\r]+/u', ' ', $str);
		$str = preg_replace('/\s{2,}/u', ' ', $str);

		// Trim white space and return
		return trim($str);
	}

	/**
	 * Runs a string through Markdown.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function parseMarkdown($str)
	{
		if (!class_exists('\Markdown_Parser', false))
		{
			require_once craft()->path->getFrameworkPath().'vendors/markdown/markdown.php';
		}

		$md = new \Markdown_Parser();
		return $md->transform($str);
	}

	/**
	 * Runs a string through Markdown, but remoes any paragraph tags that get removed
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function parseMarkdownLine($str)
	{
		// Prevent line breaks from getting treated as paragraphs
		$str = preg_replace('/[\r\n]/', '  $0', $str);

		// Parse with Markdown
		$str = self::parseMarkdown($str);

		// Return without the <p> and </p>
		$str = trim(str_replace(array('<p>', '</p>'), '', $str));

		return $str;
	}

	/**
	 * Attempts to convert a string to UTF-8 and clean any non-valid UTF-8 characters.
	 *
	 * @param      $string
	 *
	 * @return bool|string
	 */
	public static function convertToUTF8($string)
	{
		// Don't wrap in a class_exists in case the server already has it's own version of HTMLPurifier and they have
		// open_basedir restrictions
		require_once Craft::getPathOfAlias('system.vendors.htmlpurifier').'/HTMLPurifier.standalone.php';

		// If it's already a UTF8 string, just clean and return it
		if (static::isUTF8($string))
		{
			return \HTMLPurifier_Encoder::cleanUTF8($string);
		}

		// Otherwise set HTMLPurifier to the actual string encoding
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('Core.Encoding', static::getEncoding($string));

		// Clean it
		$string = \HTMLPurifier_Encoder::cleanUTF8($string);

		// Convert it to UTF8 if possible
		if (static::checkForIconv())
		{
			$string = \HTMLPurifier_Encoder::convertToUTF8($string, $config, null);
		}
		else
		{
			$encoding = static::getEncoding($string);
			$string = mb_convert_encoding($string, 'utf-8', $encoding);
		}

		return $string;
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
		if (max(array_map('ord', str_split($string))) >= 240)
		{
			$string = preg_replace_callback('/./u', function(array $match)
			{
				if (strlen($match[0]) >= 4)
				{
					// (Logic pulled from WP's wp_encode_emoji() function)
					// UTF-32's hex encoding is the same as HTML's hex encoding.
					// So, by converting from UTF-8 to UTF-32, we magically
					// get the correct hex encoding.
					$unpacked = unpack('H*', mb_convert_encoding($match[0], 'UTF-32', 'UTF-8'));
					return isset($unpacked[1]) ? '&#x'.ltrim($unpacked[1], '0').';' : '';
				}
				else
				{
					return $match[0];
				}
			}, $string);
		}

		return $string;
	}

	/**
	 * Returns whether iconv is installed and not buggy.
	 *
	 * @return bool
	 */
	public static function checkForIconv()
	{
		if (!isset(static::$_iconv))
		{
			// Check if iconv is installed. Note we can't just use HTMLPurifier_Encoder::iconvAvailable() because they
			// don't consider iconv "installed" if it's there but "unusable".
			if (function_exists('iconv') && \HTMLPurifier_Encoder::testIconvTruncateBug() === \HTMLPurifier_Encoder::ICONV_OK)
			{
				static::$_iconv = true;
			}
			else
			{
				static::$_iconv = false;
			}
		}

		return static::$_iconv;
	}

	/**
	 * Checks if the given string is UTF-8 encoded.
	 *
	 * @param $string The string to check.
	 *
	 * @return bool
	 */
	public static function isUTF8($string)
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
		return StringHelper::toLowerCase(mb_detect_encoding($string, mb_detect_order(), true));
	}

	/**
	 * Returns a multibyte aware upper-case version of a string. Note: Not using mb_strtoupper because of
	 * {@see https://bugs.php.net/bug.php?id=47742}.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function toUpperCase($string)
	{
		return mb_convert_case($string, MB_CASE_UPPER, static::UTF8);
	}

	/**
	 * Returns a multibyte aware lower-case version of a string. Note: Not using mb_strtoupper because of
	 * {@see https://bugs.php.net/bug.php?id=47742}.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function toLowerCase($string)
	{
		return mb_convert_case($string, MB_CASE_LOWER, static::UTF8);
	}

	/**
	 * Uppercases the first character of a multibyte string.
	 *
	 * @param string $string The multibyte string.
	 *
	 * @return string The string with the first character converted to upercase.
	 */
	public static function uppercaseFirst($string)
	{
		$strlen = mb_strlen($string, static::UTF8);
		$firstChar = mb_substr($string, 0, 1, static::UTF8);
		$remainder = mb_substr($string, 1, $strlen - 1, static::UTF8);
		return static::toUpperCase($firstChar).$remainder;
	}

	/**
	 * Lowercases the first character of a multibyte string.
	 *
	 * @param string $string The multibyte string.
	 *
	 * @return string The string with the first character converted to lowercase.
	 */
	public static function lowercaseFirst($string)
	{
		$strlen = mb_strlen($string, static::UTF8);
		$firstChar = mb_substr($string, 0, 1, static::UTF8);
		$remainder = mb_substr($string, 1, $strlen - 1, static::UTF8);
		return static::toLowerCase($firstChar).$remainder;
	}

	/**
	 * kebab-cases a string.
	 *
	 * @param string $string The string
	 * @param string $glue The string used to glue the words together (default is a hyphen)
	 * @param boolean $lower Whether the string should be lowercased (default is true)
	 * @param boolean $removePunctuation Whether punctuation marks should be removed (default is true)
	 *
	 * @return string
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

		if (!$words)
		{
			return '';
		}

		$string = array_shift($words).implode('', array_map(array(get_called_class(), 'uppercaseFirst'), $words));

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
		$string = implode('', array_map(array(get_called_class(), 'uppercaseFirst'), $words));

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
	 * @return string[]
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
	 * @return string
	 */
	public static function stripHtml($str)
	{
		return preg_replace('/<(.*?)>/u', '', $str);
	}

	/**
	 * Backslash-escapes any commas in a given string.
	 *
	 * @param $str The string.
	 *
	 * @return string
	 */
	public static function escapeCommas($str)
	{
		return preg_replace('/(?<!\\\),/', '\,', $str);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Get array of chars to be used for conversion.
	 *
	 * @return array
	 */
	private static function _getCharMap()
	{
		// Keep local copy
		static $map = array();

		if (empty($map))
		{
			// This will replace accented chars with non-accented chars
			foreach (static::getAsciiCharMap() AS $k => $v)
			{
				$map[static::_chr($k)] = $v;
			}

			// Replace punctuation with a space
			foreach (static::getAsciiPunctuation() AS $i)
			{
				$map[static::_chr($i)] = ' ';
			}
		}

		// Return the char map
		return $map;
	}

	/**
	 * Custom alternative to chr().
	 *
	 * @param int $int
	 *
	 * @return string
	 */
	private static function _chr($int)
	{
		return html_entity_decode("&#{$int};", ENT_QUOTES, static::UTF8);
	}

	/**
	 * Prepares a string for casing routines.
	 *
	 * @param string $string The string
	 * @param
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
		if ($lower)
		{
			// Make it lowercase
			$string = self::toLowerCase($string);
		}

		if ($removePunctuation)
		{
			$string = str_replace(array('.', '_', '-'), ' ', $string);
		}

		// Remove inner-word punctuation.
		$string = preg_replace('/[\'"‘’“”\[\]\(\)\{\}:]/u', '', $string);

		// Split on the words and return
		return self::splitOnWords($string);
	}
}
