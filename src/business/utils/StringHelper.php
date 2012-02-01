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
}
