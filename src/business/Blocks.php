<?php
namespace Blocks;

require_once dirname(__FILE__).'/../blocks_info.php';

/**
 *
*/
class Blocks extends \Yii
{
	private static $_storedBlocksInfo;

	/**
	 * Returns the @@@productDisplay@@@ version number, as defined by the BLOCKS_VERSION constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getVersion()
	{
		return BLOCKS_VERSION;
	}

	/**
	 * Returns the @@@productDisplay@@@ version number, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredVersion()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->version : null;
	}

	/**
	 * Returns the @@@productDisplay@@@ build number, as defined by the BLOCKS_BUILD constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getBuild()
	{
		return BLOCKS_BUILD;
	}

	/**
	 *
	 * Returns the @@@productDisplay@@@ build number, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredBuild()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->build : null;
	}

	/**
	 * Returns the @@@productDisplay@@@ release date, as defined by the BLOCKS_RELEASE_DATE constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getReleaseDate()
	{
		return BLOCKS_RELEASE_DATE;
	}

	/**
	 * Returns the @@@productDisplay@@@ release date, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredReleaseDate()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->releaseDate : null;
	}

	/**
	 * Returns the site name.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteName()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->siteName : null;
	}

	/**
	 * Returns the site URL.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteUrl()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->siteUrl : null;
	}

	/**
	 * Returns the site language.
	 *
	 * @static
	 * @return string
	 */
	public static function getLanguage()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->language : null;
	}

	/**
	 * Returns the license key.
	 *
	 * @static
	 * @return string
	 */
	public static function getLicenseKey()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->licenseKey : null;
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @static
	 * @return bool
	 */
	public static function isSystemOn()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->on == 1 : false;
	}

	/**
	 * Turns the system on.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOn()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		$blocksInfo = InfoRecord::model()->find();

		if ($blocksInfo)
		{
			$blocksInfo->on = true;
			if ($blocksInfo->save())
				return true;
		}

		return false;
	}

	/**
	 * Turns the system off.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOff()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		$blocksInfo = InfoRecord::model()->find();

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
	 *
	 * @static
	 * @return Info
	 */
	private static function _getStoredInfo()
	{
		if (!isset(static::$_storedBlocksInfo))
		{
			if (blx()->isInstalled())
				static::$_storedBlocksInfo = InfoRecord::model()->find();
			else
				static::$_storedBlocksInfo = false;
		}

		return static::$_storedBlocksInfo;
	}

	/**
	 * Returns the Yii framework version.
	 *
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
	 * @param string $alias
	 * @param bool   $forceInclude
	 * @throws \Exception
	 * @return string|void
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
				case 'plugins':
				{
					$rootPath = BLOCKS_PLUGINS_PATH;
					break;
				}
				default:
				{
					throw new \Exception('Unknown alias “'.$alias.'”');
				}
			}
		}
		else
		{
			$rootPath = BLOCKS_APP_PATH;
		}

		$path = $rootPath.implode('/', array_slice($segs, 1));

		$folder = (substr($path, -2) == '/*');
		if ($folder)
		{
			$path = substr($path, 0, -1);
			$files = glob($path."*.php");
			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					static::_importFile(realpath($file));
				}
			}
		}
		else
		{
			$file = $path.'.php';
			static::_importFile($file);

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

		if (($currentUser = blx()->accounts->getCurrentUser()) !== null)
			$userId = $currentUser->id;
		else
			$userId = null;

		$logger = static::getLogger();
		$logger->log($userId.'///'.$msgKey.'///'.$encodedData, 'activity', $category);
	}

	/**
	 * @static
	 * @param string $message
	 * @param array  $params
	 * @param string $source
	 * @param string $language
	 * @param string $category
	 * @return string|null
	 */
	public static function t($message, $params = array(), $source = null, $language = null, $category = 'blocks')
	{
		// Normalize the param keys
		$normalizedParams = array();
		if (is_array($params))
		{
			foreach ($params as $key => $value)
			{
				$key = '{'.trim($key, '{}').'}';
				$normalizedParams[$key] = $value;
			}
		}

		$translation = parent::t($category, $message, $normalizedParams, $source, $language);
		if (blx()->config->translationDebugOutput)
			$translation = '@'.$translation.'@';

		return $translation;
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
