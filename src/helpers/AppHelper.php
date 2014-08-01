<?php
namespace Craft;

/**
 * Class AppHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class AppHelper
{
	/**
	 * @var null
	 */
	private static $_isPhpDevServer = null;

	/**
	 * Returns whether Craft is running on the dev server bundled with PHP 5.4+
	 *
\	 * @return bool
	 */
	public static function isPhpDevServer()
	{
		if (!isset(static::$_isPhpDevServer))
		{
			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				static::$_isPhpDevServer = (strncmp($_SERVER['SERVER_SOFTWARE'], 'PHP', 3) == 0);
			}
			else
			{
				static::$_isPhpDevServer = false;
			}
		}

		return static::$_isPhpDevServer;
	}

	/**
	 * Returns an array of all known Craft editions.
	 *
	 * @return array
	 */
	public static function getEditions()
	{
		return array(Craft::Personal, Craft::Client, Craft::Pro);
	}

	/**
	 * Returns the name of the given Craft edition.
	 *
	 * @param int $edition
	 *
	 * @return string
	 */
	public static function getEditionName($edition)
	{
		switch ($edition)
		{
			case Craft::Client:
			{
				return 'Client';
			}
			case Craft::Pro:
			{
				return 'Pro';
			}
			default:
			{
				return 'Personal';
			}
		}
	}

	/**
	 * Returns whether an edition is valid.
	 *
	 * @param mixed $edition
	 *
	 * @return bool
	 */
	public static function isValidEdition($edition)
	{
		return (is_numeric($edition) && in_array($edition, static::getEditions()));
	}

	/**
	 * Return a byte value from a size string formatted the way PHP likes it (for example - 64M).
	 *
	 * @param string $value
	 *
	 * @return int
	 */
	public static function getByteValueFromPhpSizeString($value)
	{
		$matches = array();

		// See if we can recognize that.
		if (!preg_match('/[0-9]+(K|M|G|T)/i', $value, $matches))
		{
			return (int) $value;
		}

		// Multiply! Falling through here is intentional.
		switch ($matches[1])
		{
			case 'T':
				$value *= 1024;
			case 'G':
				$value *= 1024;
			case 'M':
				$value *= 1024;
			case 'K':
				$value *= 1024;
		}

		return $value;
	}
}
