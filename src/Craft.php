<?php
namespace craft\app;

use craft\app\models\Info as InfoModel;

/**
 * Craft is helper class serving common Craft and Yii framework functionality.
 *
 * It encapsulates {@link \Yii} and ultimately {@link \YiiBase}, which provides the actual implementation.
 *
 * It also defines the global craft() method, which is a wrapper for the Craft::app() singleton.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app
 * @since     3.0
 */
class Craft extends \Yii
{
	// Constants
	// =========================================================================

	// Edition constants
	const Personal = 0;
	const Client   = 1;
	const Pro      = 2;

	// Public Methods
	// =========================================================================

	/**
	 * Displays a variable.
     *
	 * @param mixed $target    The variable to be dumped.
	 * @param int   $depth     The maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param bool  $highlight Whether the result should be syntax-highlighted. Defaults to true.
	 *
	 * @return null
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
	 *
	 * @return null
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
	 * Translates a given message into the specified language. If the config setting 'translationDebugOutput' is set,
	 * the the output will be wrapped in a pair of '@' to help diagnose any missing translations.
	 *
	 * @param string      $message   The original source message.
	 * @param array       $variables An associative array of key => value pairs to be applied to the message using `strtr`.
	 * @param string|null $source    Defines which message source application component to use. Defaults to null,
	 *                               meaning use 'coreMessages' for messages belonging to the 'yii' category and using
	 *                               'messages' for messages belonging to Craft.
	 * @param string|null $language  The target language. If set to null (default), craft()->getLanguage() will be used.
	 * @param string      $category  The message category. Please use only word letters. Note, category 'craft' is
	 *                               reserved for Craft and 'yii' is reserved for the Yii framework.
	 *
	 * @return string|null The translated message, or null if the source key could not be found.
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
	 * Logs a message.  Messages logged by this method may be retrieved via {@link Logger::getLogs} and may be recorded
	 * in different media, such as file, email, database, using {@link LogRouter}.
	 *
	 * @param string $msg      The message to be logged.
	 * @param string $level    The level of the message (e.g. LogLevel::Trace', LogLevel::Info, LogLevel::Warning or
	 *                         LogLevel::Error).
	 *                         Defaults to LogLevel::Info.
	 * @param bool   $force    Whether to force the message to be logged regardless of the level or category.
	 * @param string $category The category of the message (e.g. 'application'). It is case-insensitive.
	 * @param string $plugin   The plugin handle that made the log call. If null, will be set to 'craft'. Use for
	 *                         determining which log file to write to.
	 *
	 * @return null
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

	// Private Methods
	// =========================================================================

	/**
	 * Imports a file into Craft's classMap.
	 *
	 * @param string $file The file to import.
	 *
	 * @return null
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
 * @return \craft\app\web\Application|\craft\app\console\Application
 */
function craft()
{
	return Craft::app();
}
