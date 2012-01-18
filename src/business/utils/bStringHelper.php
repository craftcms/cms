<?php

/**
 *
 */
class bStringHelper
{
	/**
	 * @static
	 * @param $value
	 * @return bool
	 * @throws bException
	 */
	public static function isNullOrEmpty($value)
	{
		if ($value === null || $value === '')
			return true;

		if (!is_string($value))
			throw new bException('IsNullOrEmpty requires a string.');

		return false;
	}

	/**
	 * @static
	 * @param $value
	 * @return bool
	 * @throws bException
	 */
	public static function isNotNullOrEmpty($value)
	{
		return !self::isNullOrEmpty($value);
	}
}
