<?php
namespace Blocks;


require_once dirname(__FILE__).'/../blocks_info.php';


/**
 *
*/
class Blocks extends \Yii
{
	/**
	 * @static
	 * @param bool $checkStoredEdition If true, will check the db for the edition if we can't get it locally.
	 * @return string
	 */
	public static function getEdition($checkStoredEdition = true)
	{
		if (strpos(BLOCKS_EDITION, '@@@') !== false && $checkStoredEdition)
			return self::getStoredEdition();
		else
			return BLOCKS_EDITION;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredEdition()
	{
		$info = Info::model()->find();
		return $info ? $info->edition : null;
	}

	/**
	 * @static
	 * @param bool $checkStoredVersion If true, will check the db for the version if we can't get it locally.
	 * @return string
	 */
	public static function getVersion($checkStoredVersion = true)
	{
		if (strpos(BLOCKS_VERSION, '@@@') !== false && $checkStoredVersion)
			return self::getStoredVersion();
		else
			return BLOCKS_VERSION;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredVersion()
	{
		$info = Info::model()->find();
		return $info ? $info->version : null;
	}

	/**
	 * @static
	 * @param bool $checkStoredBuild If true, will check the db for the build if we can't get it locally.
	 * @return string
	 */
	public static function getBuild($checkStoredBuild = true)
	{
		if (strpos(BLOCKS_BUILD, '@@@') !== false && $checkStoredBuild)
			return self::getStoredBuild();
		else
			return BLOCKS_BUILD;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredBuild()
	{
		$info = Info::model()->find();
		return $info ? $info->build : null;
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
		return \CVarDumper::dump($target, 10, true);
	}
}

/**
 * Returns the current b() instance
 * @return App
 */
function b()
{
	return Blocks::app();
}
