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
	 * @static
	 * @param bool $checkStoredEdition If true, will check the db for the edition if we can't get it locally.
	 * @return string
	 */
	public static function getEdition($checkStoredEdition = true)
	{
		if (strpos(self::$_edition, '@@@') !== false && $checkStoredEdition)
			self::$_edition = self::getStoredEdition();

		return self::$_edition;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredEdition()
	{
		$info = bInfo::model()->findAll();
		return !empty($info) ? $info[0]->edition : null;
	}

	/**
	 * @static
	 * @param bool $checkStoredVersion If true, will check the db for the version if we can't get it locally.
	 * @return string
	 */
	public static function getVersion($checkStoredVersion = true)
	{
		if (strpos(self::$_version, '@@@') !== false && $checkStoredVersion)
			self::$_version = self::getStoredVersion();

		return self::$_version;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredVersion()
	{
		$info = bInfo::model()->findAll();
		return !empty($info) ? $info[0]->version : null;
	}

	/**
	 * @static
	 * @param bool $checkStoredBuild If true, will check the db for the build if we can't get it locally.
	 * @return string
	 */
	public static function getBuild($checkStoredBuild = true)
	{
		if (strpos(self::$_build, '@@@') !== false && $checkStoredBuild)
			self::$_build = self::getStoredBuild();

		return self::$_build;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredBuild()
	{
		$info = bInfo::model()->findAll();
		return !empty($info) ? $info[0]->build : null;
	}

	/**
	 * @static
	 * @return mixed
	 */
	public static function getYiiVersion()
	{
		return parent::getVersion();
	}

	/**
	 * @static
	 * @param $target
	 * @return string
	 */
	public static function dump($target)
	{
		return CVarDumper::dump($target, 10, true);
	}
}
