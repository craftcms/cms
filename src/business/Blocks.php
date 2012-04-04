<?php
namespace Blocks;

require_once dirname(__FILE__).'/enums/Edition.php';
require_once dirname(__FILE__).'/../blocks_info.php';


/**
 *
*/
class Blocks extends \Yii
{
	private static $_storedBlocksInfo;
	private static $_isSystemOn;

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
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->edition : null;
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
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->version : null;
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
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->build : null;
	}

	/**
	 * @static
	 * @return bool
	 */
	public static function isSystemOn()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->on == 1 : false;
	}

	/**
	 * @static
	 * @return bool
	 */
	public static function turnSystemOn()
	{
		$storedBlocksInfo = self::_getStoredInfo();

		if ($storedBlocksInfo)
		{
			$storedBlocksInfo->on = true;
			if ($storedBlocksInfo->save())
				return true;
		}

		return false;
	}

	/**
	 * @static
	 * @return bool
	 */
	public static function turnSystemOff()
	{
		$storedBlocksInfo = self::_getStoredInfo();

		if ($storedBlocksInfo)
		{
			// save the current state of the system for possible use later in the request.
			self::$_isSystemOn = self::isSystemOn();

			// if it's not on, don't even bother.
			if (self::$_isSystemOn)
			{
				$storedBlocksInfo->on = false;
				if (!$storedBlocksInfo->save())
					return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Return the saved stored blocks info.  If it's not set, get it from the database and return it.
	 * @static
	 * @return mixed
	 */
	private static function _getStoredInfo()
	{
		if ((static::$_storedBlocksInfo) == null)
		{
			self::$_storedBlocksInfo = Info::model()->find();
		}

		return self::$_storedBlocksInfo;
	}

	/**
	 * Returns the Yii framework version.
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

	/**
	 * @static
	 * @param      $alias
	 * @param bool $forceInclude
	 */
	public static function import($alias, $forceInclude = false)
	{
		$path = BLOCKS_APP_PATH.str_replace('.', '/', $alias);

		$directory = (substr($path, -2) == '/*');
		if ($directory)
		{
			$path = substr($path, 0, -1);

			if (($files = @glob($path."*.php")) !== false)
			{
				foreach ($files as $file)
				{
					self::_importFile(realpath($file));
				}
			}
		}
		else
		{
			$file = $path.'.php';
			self::_importFile($file);

			if ($forceInclude)
				require_once $file;
		}
	}

	/**
	 * @static
	 * @param $file
	 */
	private static function _importFile($file)
	{
		$class = __NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME);
		\Yii::$classMap[$class] = $file;
	}
}

/**
 * Returns the current b() instance.  This is a wrapper function for the Blocks::app() instance.
 * @return App
 */
function b()
{
	return Blocks::app();
}
