<?php
namespace Craft;

/**
 *
 */
class Craft extends \Yii
{
	private static $_isInstalled;
	private static $_info;
	private static $_siteName;
	private static $_siteUrl;

	private static $_packageList = array('Users', 'PublishPro', 'Localize', 'Cloud', 'Rebrand');

	/**
	 * Determines if Craft is installed by checking if the info table exists.
	 *
	 * @static
	 * @return bool
	 */
	public static function isInstalled()
	{
		if (!isset(static::$_isInstalled))
		{
			try
			{
				// If the db config isn't valid, then we'll assume it's not installed.
				if (!craft()->db->isDbConnectionValid())
				{
					return false;
				}
			}
			catch (DbConnectException $e)
			{
				return false;
			}

			static::$_isInstalled = (craft()->isConsole() || craft()->db->tableExists('info', false));
		}

		return static::$_isInstalled;
	}

	/**
	 * Tells Craft that it's installed now.
	 *
	 * @static
	 */
	public static function setIsInstalled()
	{
		// If you say so!
		static::$_isInstalled = true;
	}

	/**
	 * Returns the installed Craft version.
	 *
	 * @static
	 * @return string
	 */
	public static function getVersion()
	{
		return static::getInfo('version');
	}

	/**
	 * Returns the installed Craft build.
	 *
	 * @static
	 * @return string
	 */
	public static function getBuild()
	{
		return static::getInfo('build');
	}

	/**
	 * Returns the installed Craft release date.
	 *
	 * @static
	 * @return string
	 */
	public static function getReleaseDate()
	{
		return static::getInfo('releaseDate');
	}

	/**
	 * Returns the Craft track.
	 *
	 * @static
	 * @return string
	 */
	public static function getTrack()
	{
		return static::getInfo('track');
	}

	/**
	 * Returns the packages in this Craft install, as defined in the craft_info table.
	 *
	 * @static
	 * @return array|null
	 */
	public static function getPackages()
	{
		return static::getInfo('packages');
	}

	/**
	 * Returns whether a package is included in this Craft build.
	 *
	 * @static
	 * @param $packageName
	 * @return bool
	 */
	public static function hasPackage($packageName)
	{
		return in_array($packageName, static::getPackages());
	}

	/**
	 * Requires that a given package is installed.
	 *
	 * @static
	 * @param string $packageName
	 * @throws Exception
	 */
	public static function requirePackage($packageName)
	{
		if (static::isInstalled() && !static::hasPackage($packageName))
		{
			throw new Exception(Craft::t('The {package} package is required to perform this action.', array(
				'package' => Craft::t($packageName)
			)));
		}
	}

	/**
	 * Installs a package.
	 *
	 * @static
	 * @param string $packageName
	 * @throws Exception
	 * @return bool
	 */
	public static function installPackage($packageName)
	{
		static::_validatePackageName($packageName);

		if (static::hasPackage($packageName))
		{
			throw new Exception(Craft::t('The {package} package is already installed.', array(
				'package' => Craft::t($packageName)
			)));
		}

		$installedPackages = static::getPackages();
		$installedPackages[] = $packageName;

		$info = static::getInfo();
		$info->packages = $installedPackages;
		return static::saveInfo($info);
	}

	/**
	 * Uninstalls a package.
	 *
	 * @static
	 * @param string $packageName
	 * @throws Exception
	 * @return bool
	 */
	public static function uninstallPackage($packageName)
	{
		static::_validatePackageName($packageName);

		if (!static::hasPackage($packageName))
		{
			throw new Exception(Craft::t('The {package} package isn’t installed.', array(
				'package' => Craft::t($packageName)
			)));
		}

		$installedPackages = static::getPackages();
		$index = array_search($packageName, $installedPackages);
		array_splice($installedPackages, $index, 1);

		$info = static::getInfo();
		$info->packages = $installedPackages;
		return static::saveInfo($info);
	}

	/**
	 * Returns the site name.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteName()
	{
		if (!isset(static::$_siteName))
		{
			$siteName = static::getInfo('siteName');
			static::$_siteName = craft()->config->parseEnvironmentString($siteName);
		}

		return static::$_siteName;
	}

	/**
	 * Returns the site URL.
	 *
	 * @static
	 * @param string|null $protocol The protocol to use (http or https). If none is specified, it will default to whatever's in the Site URL setting.
	 * @return string
	 */
	public static function getSiteUrl($protocol = null)
	{
		if (!isset(static::$_siteUrl))
		{
			if (defined('CRAFT_SITE_URL'))
			{
				$storedSiteUrl = CRAFT_SITE_URL;
			}
			else
			{
				$storedSiteUrl = static::getInfo('siteUrl');
			}

			if ($storedSiteUrl)
			{
				$storedSiteUrl = craft()->config->parseEnvironmentString($storedSiteUrl);

				$port = craft()->request->getPort();

				// If $port == 80, don't show it. If the port is already in the $storedSiteUrl, don't show it.
				// i.e. http://localhost:8888/craft
				if ($port == 80 || mb_strpos($storedSiteUrl, ':'.$port) !== false)
				{
					$port = '';
				}
				else
				{
					$port = ':'.$port;
				}

				static::$_siteUrl = rtrim($storedSiteUrl, '/').$port;
			}
			else
			{
				static::$_siteUrl = '';
			}
		}

		switch ($protocol)
		{
			case 'http':
			{
				return str_replace('https://', 'http://', static::$_siteUrl);
			}

			case 'https':
			{
				return str_replace('http://', 'https://', static::$_siteUrl);
			}

			default:
			{
				return static::$_siteUrl;
			}
		}
	}

	/**
	 * Returns the system time zone.
	 *
	 * @static
	 * @return string
	 */
	public static function getTimezone()
	{
		return static::getInfo('timezone');
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @static
	 * @return bool
	 */
	public static function isSystemOn()
	{
		return (bool) static::getInfo('on');
	}

	/**
	 * Returns whether the system is in maintenance mode.
	 *
	 * @static
	 * @return bool
	 */
	public static function isInMaintenanceMode()
	{
		return (bool) static::getInfo('maintenance');
	}

	/**
	 * Turns the system on.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOn()
	{
		return static::_setSystemStatus(1);
	}

	/**
	 * Turns the system off.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOff()
	{
		return static::_setSystemStatus(0);
	}

	/**
	 * Enables Maintenance Mode.
	 *
	 * @static
	 * @return bool
	 */
	public static function enableMaintenanceMode()
	{
		return static::_setMaintenanceMode(1);
	}

	/**
	 * Disables Maintenance Mode.
	 *
	 * @static
	 * @return bool
	 */
	public static function disableMaintenanceMode()
	{
		return static::_setMaintenanceMode(0);
	}

	/**
	 * Returns the info model, or just a particular attribute.
	 *
	 * @static
	 * @param string|null $attribute
	 * @throws Exception
	 * @return mixed
	 */
	public static function getInfo($attribute = null)
	{
		if (!isset(static::$_info))
		{
			if (static::isInstalled())
			{
				$row = craft()->db->createCommand()
					->from('info')
					->limit(1)
					->queryRow();

				if (!$row)
				{
					throw new Exception(Craft::t('Craft appears to be installed but the info table is empty.'));
				}

				static::$_info = new InfoModel($row);
			}
			else
			{
				static::$_info = new InfoModel();
			}
		}

		if ($attribute)
		{
			return static::$_info->getAttribute($attribute);
		}
		else
		{
			return static::$_info;
		}
	}

	/**
	 * Updates the info row.
	 *
	 * @param InfoModel $info
	 * @return bool
	 */
	public static function saveInfo(InfoModel $info)
	{
		if ($info->validate())
		{
			$attributes = $info->getAttributes(null, true);

			if (static::isInstalled())
			{
				craft()->db->createCommand()->update('info', $attributes);
			}
			else
			{
				craft()->db->createCommand()->insert('info', $attributes);

				// Set the new id
				$info->id = craft()->db->getLastInsertID();
			}

			// Use this as the new cached InfoModel
			static::$_info = $info;

			return true;
		}
		else
		{
			return false;
		}
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

		$folder = (mb_substr($path, -2) == '/*');
		if ($folder)
		{
			$path = mb_substr($path, 0, -1);
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

		$translation = parent::t($category, (string)$message, $normalizedVariables, $source, $language);
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
	 * @param string $msg      message to be logged
	 * @param string $level    level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @param bool   $force    Whether to force the message to be logged regardless of the level or category.
	 * @param string $category category of the message (e.g. 'system.web'). It is case-insensitive.
	 */
	public static function log($msg, $level = LogLevel::Info, $force = false, $category = 'application')
	{
		if ((YII_DEBUG && YII_TRACE_LEVEL > 0 && $level !== LogLevel::Profile) || $force)
		{
			$traces = debug_backtrace();
			$count = 0;

			foreach ($traces as $trace)
			{
				if (isset($trace['file'], $trace['line']) && mb_strpos($trace['file'], YII_PATH) !== 0)
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

		static::getLogger()->log($msg, $level, $force, $category);
	}

	/**
	 * Turns the system on or off.
	 *
	 * @static
	 * @access private
	 * @param bool $value
	 * @return bool
	 */
	private static function _setSystemStatus($value)
	{
		$info = static::getInfo();
		$info->on = $value;
		return static::saveInfo($info);
	}

	/**
	 * Enables or disables Maintenance Mode
	 *
	 * @access private
	 * @param bool $value
	 * @return bool
	 */
	private static function _setMaintenanceMode($value)
	{
		$info = static::getInfo();
		$info->maintenance = $value;
		return static::saveInfo($info);
	}

	/**
	 * Validates a package name.
	 *
	 * @static
	 * @access private
	 * @throws Exception
	 */
	private static function _validatePackageName($packageName)
	{
		if (!in_array($packageName, static::$_packageList))
		{
			throw new Exception(Craft::t('Craft doesn’t have a package named “{package}”', array(
				'package' => Craft::t($packageName)
			)));
		}
	}

	/**
	 * @static
	 * @param $file
	 */
	private static function _importFile($file)
	{
		$file = str_replace('\\', '/', $file);

		// Don't add any Composer vendor files to the class map.
		if (strpos($file, '/app/vendor/') === false)
		{
			$class = __NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME);
			\Yii::$classMap[$class] = $file;
		}
	}
}

/**
 * Returns the current craft() instance.  This is a wrapper function for the Craft::app() instance.
 *
 * @return WebApp|ConsoleApp
 */
function craft()
{
	return Craft::app();
}
