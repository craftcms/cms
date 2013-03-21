<?php
namespace Craft;

/**
 *
 */
class StringHelper
{
	private static $_asciiCharMap;

	/**
	 * Converts an array to a string.
	 *
	 * @static
	 * @param mixed  $arr
	 * @param string $glue
	 * @return string
	 */
	public static function arrayToString($arr, $glue = ',')
	{
		if (is_array($arr))
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
	 * @static
	 * @param $value
	 * @return bool
	 * @throws Exception
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
	 * @static
	 * @param $value
	 * @return bool
	 * @throws Exception
	 */
	public static function isNotNullOrEmpty($value)
	{
		return !static::isNullOrEmpty($value);
	}

	/**
	 * @static
	 * @param int  $length
	 * @param bool $extendedChars
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
		$numValidChars = strlen($validChars);

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
	 * @static
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

			// 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * Returns is the given string matches a UUID pattern.
	 *
	 * @param $uuid
	 * @return bool
	 */
	public static function isUUID($uuid)
	{
		return !empty($uuid) && preg_match("/[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}/uis", $uuid);
	}

	/**
	 * @static
	 * @param $string
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
	 * @static
	 * @return array
	 */
	public static function getAsciiCharMap()
	{
		if (!isset(static::$_asciiCharMap))
		{
			static::$_asciiCharMap = array(
				223 => 'ss', 224 => 'a',  225 => 'a',  226 => 'a',  229 => 'a',
				227 => 'ae', 230 => 'ae', 228 => 'ae', 231 => 'c',  232 => 'e',
				233 => 'e',  234 => 'e',  235 => 'e',  236 => 'i',  237 => 'i',
				238 => 'i',  239 => 'i',  241 => 'n',  242 => 'o',  243 => 'o',
				244 => 'o',  245 => 'o',  246 => 'oe', 249 => 'u',  250 => 'u',
				251 => 'u',  252 => 'ue', 255 => 'y',  257 => 'aa', 269 => 'ch',
				275 => 'ee', 291 => 'gj', 299 => 'ii', 311 => 'kj', 316 => 'lj',
				326 => 'nj', 353 => 'sh', 363 => 'uu', 382 => 'zh', 256 => 'aa',
				268 => 'ch', 274 => 'ee', 290 => 'gj', 298 => 'ii', 310 => 'kj',
				315 => 'lj', 325 => 'nj', 352 => 'sh', 362 => 'uu', 381 => 'zh'
			);

			foreach (craft()->config->get('customAsciiCharMappings') as $ascii => $char)
			{
				static::$_asciiCharMap[$ascii] = $char;
			}
		}

		return static::$_asciiCharMap;
	}

	/**
	 * Converts extended ASCII characters to ASCII.
	 *
	 * @static
	 * @param string $str
	 * @return string
	 */
	public static function asciiString($str)
	{
		$asciiStr = '';
		$strlen = strlen($str);
		$asciiCharMap = static::getAsciiCharMap();

		for ($c = 0; $c < $strlen; $c++)
		{
			$char = $str[$c];
			$ascii = ord($char);

			if ($ascii >= 32 && $ascii < 128)
			{
				$asciiStr .= $char;
			}
			else if (isset($asciiCharMap[$ascii]))
			{
				$asciiStr .= $asciiCharMap[$ascii];
			}
		}

		return $asciiStr;
	}

	/**
	 * Custom alternative to chr().
	 *
	 * @static
	 * @param int $int
	 * @return string
	 */
	public static function chr($int)
	{
		return html_entity_decode("&#{$int};", ENT_QUOTES, 'UTF-8');
	}
}
