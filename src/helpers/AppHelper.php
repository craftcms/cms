<?php
namespace Blocks;

/**
 *
 */
class AppHelper
{
	private static $_isPhpDevServer;

	/**
	 * Returns whether Blocks is running on the dev server bundled with PHP 5.4+
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
}
