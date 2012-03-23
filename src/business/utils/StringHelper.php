<?php
namespace Blocks;

/**
 *
 */
class StringHelper
{
	/**
	 * @static
	 * @param $value
	 * @return bool
	 * @throws Exception
	 */
	public static function isNullOrEmpty($value)
	{
		if ($value === null || $value === '')
			return true;

		if (!is_string($value))
			throw new Exception('IsNullOrEmpty requires a string.');

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
		return !self::isNullOrEmpty($value);
	}

	/**
	 * @static
	 *
	 * @param int  $length
	 * @param bool $extendedChars
	 *
	 * @return string
	 */
	public static function randomString($length = 36, $extendedChars = false)
	{
		if ($extendedChars)
			$validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"';
		else
			$validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

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
}
