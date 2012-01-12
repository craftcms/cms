<?php

/**
 *
*/
class Blocks extends Yii
{
	private static $_edition = '@@@edition@@@';
	private static $_version = '0.12';
	private static $_build = '@@@build@@@';

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function getEdition()
	{
		if (strpos(self::$_edition, '@@@') !== false)
			self::$_edition = self::getStoredEdition();

		return self::$_edition;
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return null
	 */
	public static function getStoredEdition()
	{
		$info = Info::model()->findAll();
		return !empty($info) ? $info[0]->edition : null;
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function getVersion()
	{
		if (strpos(self::$_version, '@@@') !== false)
			self::$_version = self::getStoredVersion();

		return self::$_version;
	}

	/**
	 * @access public
	 *
	 * @static
	 * @return null
	 */
	public static function getStoredVersion()
	{
		$info = Info::model()->findAll();
		return !empty($info) ? $info[0]->version : null;
	}

	/**
	 * @access public
	 *
	 * @static

	 * @return string
	 */
	public static function getBuild()
	{
		if (strpos(self::$_build, '@@@') !== false)
			self::$_build = self::getStoredBuild();

		return self::$_build;
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return null
	 */
	public static function getStoredBuild()
	{
		$info = Info::model()->findAll();
		return !empty($info) ? $info[0]->build : null;
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return mixed
	 */
	public static function getYiiVersion()
	{
		return parent::getVersion();
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @param $target
	 *
	 * @return string
	 */
	public static function dump($target)
	{
		return CVarDumper::dump($target, 10, true);
	}
}
