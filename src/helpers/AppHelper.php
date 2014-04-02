<?php
namespace Craft;

/**
 *
 */
class AppHelper
{
	private static $_isPhpDevServer;

	/**
	 * Returns whether Craft is running on the dev server bundled with PHP 5.4+
	 *
	 * @static
	 * @return bool
	 */
	public static function isPhpDevServer()
	{
		if (!isset(static::$_isPhpDevServer))
		{
			static::$_isPhpDevServer = (strncmp($_SERVER['SERVER_SOFTWARE'], 'PHP', 3) == 0);
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
	 * @return bool
	 */
	public static function isValidEdition($edition)
	{
		return (is_numeric($edition) && in_array($edition, static::getEditions()));
	}
}
