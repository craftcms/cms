<?php
namespace Craft;

/**
 * Craft is helper class serving common Craft and Yii framework functionality.
 *
 * It encapsulates {@link Yii} and ultimately {@link YiiBase}, which provides the actual implementation.
 *
 * It also defines the global craft() method, which is a wrapper for the Craft::app() singleton.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app
 * @since     1.0
 */
class Craft extends \Yii
{
	// Edition constants
	const Personal = 0;
	const Client   = 1;
	const Pro      = 2;

	/**
	 * Determines if Craft is installed by checking if the info table exists.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function isInstalled()
	{
		craft()->deprecator->log('Craft::isInstalled()', 'Craft::isInstalled() has been deprecated. Use craft()->isInstalled() instead.');
		return craft()->isInstalled();
	}

	/**
	 * Tells Craft that it's installed now.
	 *
	 * @deprecated Deprecated in 1.3.
	 */
	public static function setIsInstalled()
	{
		craft()->deprecator->log('Craft::setIsInstalled()', 'Craft::setIsInstalled() has been deprecated. Use craft()->setIsInstalled() instead.');
		craft()->setIsInstalled();
	}

	/**
	 * Returns the installed Craft version.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getVersion()
	{
		craft()->deprecator->log('Craft::getVersion()', 'Craft::getVersion() has been deprecated. Use craft()->getVersion() instead.');
		return craft()->getVersion();
	}

	/**
	 * Returns the installed Craft build.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getBuild()
	{
		craft()->deprecator->log('Craft::getBuild()', 'Craft::getBuild() has been deprecated. Use craft()->getBuild() instead.');
		return craft()->getBuild();
	}

	/**
	 * Returns the installed Craft release date.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getReleaseDate()
	{
		craft()->deprecator->log('Craft::getReleaseDate()', 'Craft::getReleaseDate() has been deprecated. Use craft()->getReleaseDate() instead.');
		return craft()->getReleaseDate();
	}

	/**
	 * Returns the Craft track.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getTrack()
	{
		craft()->deprecator->log('Craft::getTrack()', 'Craft::getTrack() has been deprecated. Use craft()->getTrack() instead.');
		return craft()->getTrack();
	}

	/**
	 * Returns whether a package is included in this Craft build.
	 *
	 * @param string $packageName The name of the package to search for.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function hasPackage($packageName)
	{
		craft()->deprecator->log('Craft::hasPackages()', 'Craft::hasPackage() has been deprecated. Use craft()->hasPackage() instead.');
		return craft()->hasPackage($packageName);
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getSiteName()
	{
		craft()->deprecator->log('Craft::getSiteName()', 'Craft::getSiteName() has been deprecated. Use craft()->getSiteName() instead.');
		return craft()->getSiteName();
	}

	/**
	 * Returns the site URL.
	 *
	 * @param string|null $protocol The protocol to use (http or https). If none is specified, it will default to whatever is in the Site URL setting.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getSiteUrl($protocol = null)
	{
		craft()->deprecator->log('Craft::getSiteUrl()', 'Craft::getSiteUrl() has been deprecated. Use craft()->getSiteUrl() instead.');
		return craft()->getSiteUrl($protocol);
	}

	/**
	 * Returns the site UID.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getSiteUid()
	{
		craft()->deprecator->log('Craft::getSiteUid()', 'Craft::getSiteUid() has been deprecated. Use craft()->getSiteUid() instead.');
		return craft()->getSiteUid();
	}

	/**
	 * Returns the system time zone.
	 *
	 * @return string
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getTimeZone()
	{
		craft()->deprecator->log('Craft::getTimeZone()', 'Craft::getTimeZone() has been deprecated. Use craft()->getTimeZone() instead.');
		return craft()->getTimeZone();
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function isSystemOn()
	{
		craft()->deprecator->log('Craft::isSystemOn()', 'Craft::isSystemOn() has been deprecated. Use craft()->isSystemOn() instead.');
		return craft()->isSystemOn();
	}

	/**
	 * Returns whether the system is in maintenance mode.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function isInMaintenanceMode()
	{
		craft()->deprecator->log('Craft::isInMaintenanceMode()', 'Craft::isInMaintenanceMode() has been deprecated. Use craft()->isInMaintenanceMode() instead.');
		return craft()->isInMaintenanceMode();
	}

	/**
	 * Enables Maintenance Mode.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function enableMaintenanceMode()
	{
		craft()->deprecator->log('Craft::enableMaintenanceMode()', 'Craft::enableMaintenanceMode() has been deprecated. Use craft()->enableMaintenanceMode() instead.');
		return craft()->enableMaintenanceMode();
	}

	/**
	 * Disables Maintenance Mode.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function disableMaintenanceMode()
	{
		craft()->deprecator->log('Craft::disableMaintenanceMode()', 'Craft::disableMaintenanceMode() has been deprecated. Use craft()->disableMaintenanceMode() instead.');
		return craft()->disableMaintenanceMode();
	}

	/**
	 * Returns the info model, or just a particular attribute.
	 *
	 * @param string|null $attribute The attribute to return information about.
	 *
	 * @throws Exception
	 * @return mixed
	 *
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getInfo($attribute = null)
	{
		craft()->deprecator->log('Craft::getInfo()', 'Craft::getInfo() has been deprecated. Use craft()->getInfo() instead.');
		return craft()->getInfo($attribute);
	}

	/**
	 * Updates the info row.
	 *
	 * @param InfoModel $info The InfoModel that you want to save.
	 *
	 * @return bool
	 * @deprecated Deprecated in 1.3.
	 */
	public static function saveInfo(InfoModel $info)
	{
		craft()->deprecator->log('Craft::saveInfo()', 'Craft::saveInfo() has been deprecated. Use craft()->saveInfo() instead.');
		return craft()->saveInfo($info);
	}

	/**
	 * Returns the Yii framework version.
	 *
	 * @return mixed
	 * @deprecated Deprecated in 1.3.
	 */
	public static function getYiiVersion()
	{
		craft()->deprecator->log('Craft::getYiiVersion()', 'Craft::getYiiVersion() has been deprecated. Use craft()->getYiiVersion() instead.');
		return craft()->getYiiVersion();
	}

	/**
	 * Displays a variable.
     *
	 * @param mixed $target    The variable to be dumped.
	 * @param int   $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param bool  $highlight Whether the result should be syntax-highlighted. Defaults to true.
	 */
	public static function dump($target, $depth = 10, $highlight = true)
	{
		\CVarDumper::dump($target, $depth, $highlight);
	}

	/**
	 * Displays a variable and ends the request. (“Dump and die”)
     *
	 * @param mixed $target    The variable to be dumped.
	 * @param int   $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param bool  $highlight Whether the result should be syntax-highlighted. Defaults to true.
	 */
	public static function dd($target, $depth = 10, $highlight = true)
	{
		static::dump($target, $depth, $highlight);
		craft()->end();
	}

	/**
	 * Takes a path alias and will import any files/folders that it contains.
	 *
	 * @param string $alias        The path alias to import.
	 * @param bool   $forceInclude If set to true, Craft will require_once the file. Defaults to false.
	 *
	 * @throws \Exception
	 * @return string|null
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
	 * Translates a given message into the specified language. If the config setting 'translationDebugOutput'
	 * is set, the the output will be wrapped in a pair of '@' to help diagnose any missing translations.
	 *
	 * @param string $message   The original source message.
	 * @param array  $variables An associative array of key => value pairs to be applied to the message using <code>strtr</code>.
	 * @param string $source    Defines which message source application component to use. Defaults to null,
	 *                          meaning use 'coreMessages' for messages belonging to the 'yii' category and using 'messages' for messages belonging to Craft.
	 * @param string $language  The target language. If set to null (default), craft()->getLanguage() will be used.
	 * @param string $category  The message category. Please use only word letters. Note, category 'craft' is reserved for
	 *                          Craft and 'yii' is reserved for the Yii framework
	 * @return string|null      The translated message, or null if the source key could not be found.
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

		// If this isn't set, presumably we can't connect to the database.
		if (!craft()->getIsDbConnectionValid())
		{
			$source = 'en_us';
			$language = 'en_us';

			// If it's a yiic/console app, just go with english.
			if (!craft()->isConsole())
			{
				$language = craft()->getTranslatedBrowserLanguage();
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
	 * Logs a message.  Messages logged by this method may be retrieved via {@link Logger::getLogs} and may be recorded in different media, such as file, email, database, using {@link LogRouter}.
	 *
	 * @param string $msg      The message to be logged.
	 * @param string $level    The level of the message (e.g. LogLevel::Trace', LogLevel::Info, LogLevel::Warning or LogLevel::Error). Defaults to LogLevel::Info.
	 * @param bool   $force    Whether to force the message to be logged regardless of the level or category.
	 * @param string $category The category of the message (e.g. 'application'). It is case-insensitive.
	 * @param string $plugin   The plugin handle that made the log call. If null, will be set to 'craft'. Use for determining which log file to write to.
	 */
	public static function log($msg, $level = LogLevel::Info, $force = false, $category = 'application', $plugin = null)
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

		if (!$plugin)
		{
			$plugin = 'craft';
		}

		static::getLogger()->log($msg, $level, $force, $category, $plugin);
	}

	/**
	 * Imports a file into Craft's classMap.
	 *
	 * @param string $file The file to import.
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
 * Returns the current craft() instance. This is a wrapper function for the Craft::app() instance.
 *
 * @return WebApp|ConsoleApp
 */
function craft()
{
	return Craft::app();
}
