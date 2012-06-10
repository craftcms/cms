<?php
namespace Blocks;

require_once dirname(__FILE__).'/enums/Product.php';
require_once dirname(__FILE__).'/../blocks_info.php';


/**
 *
*/
class Blocks extends \Yii
{
	private static $_storedBlocksInfo;

	/**
	 * @static
	 * @return string
	 */
	public static function getProduct()
	{
		if (strpos(BLOCKS_PRODUCT, '@@@') !== false)
			return '';
		else
			return BLOCKS_PRODUCT;
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
	 * @param bool $checkStoredBuild If true, will check the db for the release date if we can't get it locally.
	 * @return string
	 */
	public static function getReleaseDate($checkStoredBuild = true)
	{
		if (strpos(BLOCKS_RELEASE_DATE, '@@@') !== false && $checkStoredBuild)
			return self::getStoredReleaseDate();
		else
			return BLOCKS_RELEASE_DATE;
	}

	/**
	 * @static
	 * @return null
	 */
	public static function getStoredReleaseDate()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->release_date : null;
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
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		$blocksInfo = Info::model()->find();

		if ($blocksInfo)
		{
			$blocksInfo->on = true;
			if ($blocksInfo->save())
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
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		$blocksInfo = Info::model()->find();

		if ($blocksInfo)
		{
			$blocksInfo->on = false;
			if ($blocksInfo->save())
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
		\CVarDumper::dump($target, 10, true);
	}

	/**
	 * @static
	 * @param      $alias
	 * @param bool $forceInclude
	 */
	public static function import($alias, $forceInclude = false)
	{
		$segs = explode('.', $alias);
		if (isset($segs[0]))
		{
			switch ($segs[0])
			{
				case 'app':
				{
					$rootPath = BLOCKS_APP_PATH;
					break;
				}
				case 'config':
				{
					$rootPath = BLOCKS_CONFIG_PATH;
					break;
				}
				case 'plugins':
				{
					$rootPath = BLOCKS_PLUGINS_PATH;
					break;
				}
				case 'runtime':
				{
					$rootPath = BLOCKS_RUNTIME_PATH;
					break;
				}
				case 'templates':
				{
					$rootPath = BLOCKS_TEMPLATES_PATH;
					break;
				}
				default:
				{
					$rootPath = BLOCKS_APP_PATH;
				}
			}
		}
		else
		{
			$rootPath = BLOCKS_APP_PATH;
		}

		$path = $rootPath.implode('/', array_slice($segs, 1));

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
	 * @param string $category
	 * @param        $msgKey
	 * @param array  $data
	 */
	public static function logActivity($category, $msgKey, $data = array())
	{
		$encodedData = Json::encode($data);

		if (($currentUser = blx()->users->getCurrentUser()) !== null)
			$userId = $currentUser->id;
		else
			$userId = null;

		$logger = self::getLogger();
		$logger->log($userId.'///'.$msgKey.'///'.$encodedData, 'activity', $category);
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
 * Returns the current blx() instance.  This is a wrapper function for the Blocks::app() instance.
 * @return App
 */
function blx()
{
	return Blocks::app();
}
