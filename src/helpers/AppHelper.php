<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;

/**
 * App helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppHelper
{
	// Properties
	// =========================================================================

	/**
	 * @var null
	 */
	private static $_isPhpDevServer = null;

	/**
	 * @var
	 */
	private static $_iconv;

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
		return [Craft::Personal, Craft::Client, Craft::Pro];
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
	 * Retrieves a boolean PHP config setting and normalizes it to an actual bool.
	 *
	 * @param string $var The PHP config setting to retrieve.
	 * @return bool Whether it is set to the php.ini equivelant of `true`.
	 */
	public static function getPhpConfigValueAsBool($var)
	{
		$value = ini_get($var);

		// Supposedly “On” values will always be normalized to '1' but who can trust PHP...
		return ($value == '1' || strtolower($value) == 'on');
	}

	/**
	 * Retrieves a PHP config setting that represents a filesize and normalizes it to bytes.
	 *
	 * @param string $var The PHP config setting to retrieve.
	 *
	 * @return int The size in bytes.
	 */
	public static function getPhpConfigValueInBytes($var)
	{
		$value = ini_get($var);

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

	public static function checkForValidIconv()
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
}
