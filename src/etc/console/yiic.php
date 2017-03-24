<?php

// We're already in the app/etc/console/ folder, so let's use that as the starting point. Make sure it doesn't look like we're on a
// network share that starts with \\
$appPath = realpath(dirname(dirname(dirname(__FILE__))));

if (isset($appPath[0]) && isset($appPath[1]))
{
	if ($appPath[0] !== '\\' && $appPath[1] !== '\\')
	{
		$appPath = str_replace('\\', '/', $appPath);
	}
}

$frontConfigPath = false;

// See if --configPath is specified from the command line.  If so, use that.
if (isset($_SERVER['argv']))
{
	foreach ($argv as $key => $arg)
	{
		if (strpos($arg, '--configPath=') !== false)
		{
			$parts = explode('=', $arg);
			$frontConfigPath = realpath($parts[1]).'/';
			unset($_SERVER['argv'][$key]);
			break;
		}
	}
}

defined('CRAFT_APP_PATH') || define('CRAFT_APP_PATH', $appPath.'/');
defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', CRAFT_APP_PATH.'vendor/');
defined('CRAFT_FRAMEWORK_PATH') || define('CRAFT_FRAMEWORK_PATH', CRAFT_APP_PATH.'framework/');;

// The app/ folder goes inside craft/ by default, so work backwards from app/
defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', realpath(CRAFT_APP_PATH.'..').'/');

// Everything else should be relative from craft/ by default
defined('CRAFT_CONFIG_PATH')       || define('CRAFT_CONFIG_PATH',       ($frontConfigPath ?: CRAFT_BASE_PATH.'config/'));
defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');
defined('CRAFT_STORAGE_PATH')      || define('CRAFT_STORAGE_PATH',      CRAFT_BASE_PATH.'storage/');
defined('CRAFT_TEMPLATES_PATH')    || define('CRAFT_TEMPLATES_PATH',    CRAFT_BASE_PATH.'templates/');
defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', CRAFT_BASE_PATH.'translations/');
defined('CRAFT_ENVIRONMENT')       || define('CRAFT_ENVIRONMENT',       'console');

/**
 * Yii command line script file configured for Craft.
 */

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

ini_set('log_errors', 1);
ini_set('error_log', CRAFT_STORAGE_PATH.'runtime/logs/phperrors.log');

error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);

require_once dirname(__FILE__).'/../../framework/yii.php';
require_once CRAFT_APP_PATH.'Craft.php';
require_once CRAFT_APP_PATH.'Info.php';

// Guzzle makes use of these PHP constants, but they aren't actually defined in some compilations of PHP.
// See http://it.blog.adclick.pt/php/fixing-php-notice-use-of-undefined-constant-curlopt_timeout_ms-assumed-curlopt_timeout_ms/
defined('CURLOPT_TIMEOUT_MS')        || define('CURLOPT_TIMEOUT_MS',        155);
defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

// Load up Composer's files
require CRAFT_VENDOR_PATH.'autoload.php';

// Disable the PHP include path
Yii::$enableIncludePath = false;

require_once(dirname(__FILE__).'/ConsoleApp.php');

// Because CHttpRequest is one of those stupid Yii files that has multiple classes defined in it.
require_once(CRAFT_APP_PATH.'framework/web/CHttpRequest.php');

Yii::setPathOfAlias('app', CRAFT_APP_PATH);
Yii::setPathOfAlias('plugins', CRAFT_PLUGINS_PATH);

$configPath = dirname(__FILE__).'/../config/console.php';
$app = Yii::createApplication('Craft\ConsoleApp', $configPath);
$app->commandRunner->addCommands(Craft\Craft::getPathOfAlias('application.consolecommands.*'));

$app->run();
