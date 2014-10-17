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
	// Properties
	// =========================================================================

	/**
	 * @var null
	 */
	private static $_isPhpDevServer = null;

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether Craft is running on the dev server bundled with PHP 5.4+.
	 *
	 * @return bool Whether Craft is running on the PHP Dev Server.
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
	 * Returns an array of all known Craft editions’ IDs.
	 *
	 * @return array All the known Craft editions’ IDs.
	 */
	public static function getEditions()
	{
		return array(Craft::Personal, Craft::Client, Craft::Pro);
	}

	/**
	 * Returns the name of the given Craft edition.
	 *
	 * @param int $edition An edition’s ID.
	 *
	 * @return string The edition’s name.
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
	 * @param mixed $edition An edition’s ID (or is it?)
	 *
	 * @return bool Whether $edition is a valid edition ID.
	 */
	public static function isValidEdition($edition)
	{
		return (is_numeric($edition) && in_array($edition, static::getEditions()));
	}

	/**
	 * Return a byte value from a size string formatted the way PHP likes it (for example - 64M).
	 *
	 * @param string $value The size string.
	 *
	 * @return int The size in bytes.
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
		switch (strtolower($matches[1]))
		{
			case 't':
				$value *= 1024;
			case 'g':
				$value *= 1024;
			case 'm':
				$value *= 1024;
			case 'k':
				$value *= 1024;
		}

		return $value;
	}
}
