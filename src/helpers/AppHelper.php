<?php
namespace Craft;

/**
 * Class AppHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
	 * @param int The size in bytes.
	 */
	public static function getPhpConfigValueInBytes($var)
	{
		$value = ini_get($var);

		return static::_normalizePhpConfigValueToBytes($value);
	}

	// Deprecated Methods
	// -------------------------------------------------------------------------

	/**
	 * Return a byte value from a size string formatted the way PHP likes it (for example - 64M).
	 *
	 * @param string $value The size string.
	 *
	 * @deprecated Deprecated in 2.3. Use {@link getPhpConfigValueInBytes()} instead.
	 * @return int The size in bytes.
	 */
	public static function getByteValueFromPhpSizeString($value)
	{
		craft()->deprecator->log('AppHelper::getByteValueFromPhpSizeString()', 'AppHelper::getByteValueFromPhpSizeString() has been deprecated. Use getPhpConfigValueInBytes() instead.');
		return static::_normalizePhpConfigValueToBytes($value);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes a PHP config value into bytes.
	 *
	 * Used by getPhpConfigValueInBytes() and getByteValueFromPhpSizeString() so long as we have to keep the latter around.
	 *
	 * @param mixed $var
	 * @return int
	 */
	private static function _normalizePhpConfigValueToBytes($value)
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
