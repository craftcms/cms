<?php

/**
 *
 */
class StringHelper
{
	/**
	 * @access public
	 *
	 * @static
	 *
	 * @param $value
	 *
	 * @return bool
	 *
	 * @throws BlocksException
	 */
	public static function IsNullOrEmpty($value)
	{
		if (!isset($value))
			return true;

		// trim does a cast-to-string before trimming and will return 'Array' giving a false positive.
		if (!is_string($value) || $value != trim($value))
			throw new BlocksException('IsNullOrEmpty requires a string.');

		return $value === '' ? true : false;
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @param $value
	 *
	 * @return bool
	 *
	 * @throws BlocksException
	 */
	public static function IsNotNullOrEmpty($value)
	{
		if (!isset($value))
			return false;

		// trim does a cast-to-string before trimming and will return 'Array' giving a false positive.
		if (!is_string($value) || $value != trim($value))
			throw new BlocksException('IsNotNullOrEmpty requires a string.');

		return $value !== '' ? true : false;
	}
}
