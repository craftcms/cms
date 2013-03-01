<?php
namespace Craft;

/**
 *
 */
class Craft extends \Yii
{
	private static $_storedCraftInfo;
	private static $_packages;
	private static $_siteUrl;

	/**
	 * Returns the Craft version number, as defined by the CRAFT_VERSION constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getVersion()
	{
		return CRAFT_VERSION;
	}

	/**
	 * Returns the Craft version number, as defined in the craft_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredVersion()
	{
		$storedCraftInfo = static::_getStoredInfo();
		return $storedCraftInfo ? $storedCraftInfo->version : null;
	}

	/**
	 * Returns the Craft build number, as defined by the CRAFT_BUILD constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getBuild()
	{
		return CRAFT_BUILD;
	}

	/**
	 *
	 * Returns the Craft build number, as defined in the craft_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredBuild()
	{
		$storedCraftInfo = static::_getStoredInfo();
		return $storedCraftInfo ? $storedCraftInfo->build : null;
	}

	/**
	 * Returns the Craft release date, as defined by the CRAFT_RELEASE_DATE constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getReleaseDate()
	{
		return CRAFT_RELEASE_DATE;
	}

	/**
	 * Returns the Craft release date, as defined in the craft_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredReleaseDate()
	{
		$storedCraftInfo = static::_getStoredInfo();
		return $storedCraftInfo ? $storedCraftInfo->releaseDate : null;
	}

	/**
	 * Returns the packages in this Craft install, as defined in the craft_info table.
	 *
	 * @static
	 * @return array|null
	 */
	public static function getPackages()
	{
		$storedCraftInfo = static::_getStoredInfo();

		if ($storedCraftInfo)
		{
			$storedPackages = array_filter(ArrayHelper::stringToArray($storedCraftInfo->packages));
			sort($storedPackages);
			return $storedPackages;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Invalidates the cached Info so it is pulled fresh the next time it is needed.
	 */
	public static function invalidateCachedInfo()
	{
		static::$_storedCraftInfo = null;
	}

	/**
	 * Returns the minimum required build number, as defined in the CRAFT_MIN_BUILD_REQUIRED constant.
	 *
	 * @return mixed
	 */
	public static function getMinRequiredBuild()
	{
		return CRAFT_MIN_BUILD_REQUIRED;
	}

	/**
	 * Returns whether a package is included in this Craft build.
	 *
	 * @param $packageName
	 * @return bool
	 */
	public static function hasPackage($packageName)
	{
		// If Craft is already installed, the check the database to determine if a package is installed or not.
		if (craft()->isInstalled())
		{
			return in_array($packageName, static::getPackages());
		}
		else
		{
			return false;
		}
	}

	/**
	 * Requires that a given package is installed.
	 *
	 * @param string $packageName
	 * @throws Exception
	 */
	public static function requirePackage($packageName)
	{
		if (!static::hasPackage($packageName) && craft()->isInstalled())
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Returns the site name.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteName()
	{
		$storedCraftInfo = static::_getStoredInfo();
		return $storedCraftInfo ? $storedCraftInfo->siteName : null;
	}

	/**
	 * Returns the site URL.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteUrl()
	{
		if (!isset(static::$_siteUrl))
		{
			$storedCraftInfo = static::_getStoredInfo();
			if ($storedCraftInfo)
			{
				$port = craft()->request->getPort();

				if ($port == 80)
				{
					$port = '';
				}
				else
				{
					$port = ':'.$port;
				}

				static::$_siteUrl = rtrim($storedCraftInfo->siteUrl, '/').$port;
			}
			else
			{
				static::$_siteUrl = '';
			}
		}

		return static::$_siteUrl;
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @static
	 * @return bool
	 */
	public static function isSystemOn()
	{
		$storedCraftInfo = static::_getStoredInfo();
		return $storedCraftInfo ? $storedCraftInfo->on == 1 : false;
	}

	/**
	 * Returns whether the system is in maintenance mode.
	 *
	 * @static
	 * @return bool
	 */
	public static function isInMaintenanceMode()
	{
		// Don't use the the static property $_storedCraftInfo.  We want the latest info possible.
		// Not using Active Record here to prevent issues with determining maintenance mode status during a migration
		if (craft()->isInstalled())
		{
			$storedCraftInfo = static::_getStoredInfo();
			return $storedCraftInfo ? $storedCraftInfo->maintenance == 1 : false;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function enableMaintenanceMode()
	{
		// Not using Active Record here to prevent issues with turning the site on/off during a migration
		if (craft()->db->createCommand()->update('info', array('maintenance' => 1)) > 0)
		{
			static::$_storedCraftInfo->maintenance = 1;
			return true;
		}

		return false;
	}

	/**
	 * @static
	 * @return bool
	 */
	public static function disableMaintenanceMode()
	{
		// Not using Active Record here to prevent issues with turning the site on/off during a migration
		if (craft()->db->createCommand()->update('info', array('maintenance' => 0)) > 0)
		{
			static::$_storedCraftInfo->maintenance = 0;
			return true;
		}

		return false;
	}

	/**
	 * Turns the system on.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOn()
	{
		// Not using Active Record here to prevent issues with turning the site on/off during a migration
		if (craft()->db->createCommand()->update('info', array('on' => 1)) > 0)
		{
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
		// Not using Active Record here to prevent issues with turning the site on/off during a migration
		if (craft()->db->createCommand()->update('info', array('on' => 0)) > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Return the saved stored Craft info.  If it's not set, get it from the database and return it.
	 *
	 * @static
	 * @return InfoRecord
	 */
	private static function _getStoredInfo()
	{
		if (!isset(static::$_storedCraftInfo))
		{
			if (craft()->isInstalled())
			{
				static::$_storedCraftInfo = InfoRecord::model()->find();
			}
			else
			{
				static::$_storedCraftInfo = false;
			}
		}

		return static::$_storedCraftInfo;
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

		if ($segs)
		{
			$firstSeg = array_shift($segs);

			switch ($firstSeg)
			{
				case 'app':
				{
					$rootPath = CRAFT_APP_PATH;
					break;
				}
				case 'plugins':
				{
					$rootPath = CRAFT_PLUGINS_PATH;
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
			$rootPath = CRAFT_APP_PATH;
		}

		$path = $rootPath.implode('/', $segs);

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
			{
				require_once $file;
			}
		}
	}

	/**
	 * @static
	 * @param string $message
	 * @param array  $variables
	 * @param string $source
	 * @param string $language
	 * @param string $category
	 * @return string|null
	 */
	public static function t($message, $variables = array(), $source = null, $language = null, $category = 'craft')
	{
		// Normalize the param keys
		$normalizedVariables = array();
		if (is_array($variables))
		{
			foreach ($variables as $key => $value)
			{
				$key = '{'.trim($key, '{}').'}';
				$normalizedVariables[$key] = $value;
			}
		}

		$translation = parent::t($category, $message, $normalizedVariables, $source, $language);
		if (craft()->config->get('translationDebugOutput'))
		{
			$translation = '@'.$translation.'@';
		}

		return $translation;
	}

	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved via {@link CLogger::getLogs} and may be recorded in different media, such as file, email, database, using {@link CLogRouter}.
	 *
	 * @param string $msg message to be logged
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @param string $category category of the message (e.g. 'system.web'). It is case-insensitive.
	 */
	public static function log($msg, $level = \CLogger::LEVEL_INFO, $category = 'application')
	{
		if (YII_DEBUG && YII_TRACE_LEVEL > 0 && $level !== \CLogger::LEVEL_PROFILE)
		{
			$traces = debug_backtrace();
			$count = 0;

			foreach ($traces as $trace)
			{
				if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII_PATH) !== 0)
				{
					$msg .= "\nin ".$trace['file'].' ('.$trace['line'].')';

					if (++$count >= YII_TRACE_LEVEL)
					{
						break;
					}
				}
			}
		}

		if (craft()->isConsole())
		{
			echo $msg."\n";
		}

		static::getLogger()->log($msg, $level, $category);
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
 * Returns the current craft() instance.  This is a wrapper function for the Craft::app() instance.
 * @return WebApp|ConsoleApp
 */
function craft()
{
	return Craft::app();
}
