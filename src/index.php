<?php

// Make sure this is PHP 5.3 or later
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('@@@appName@@@ requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
}

/**
 * Quit early if this is just an omitScriptNameInUrls or usePathInfo test request
 */

// Is this a script name redirect test?
if ((isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testScriptNameRedirect')
	|| (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'testScriptNameRedirect') !== false))
{
	exit('success');
}

// Is this a PATH_INFO test?
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testPathInfo')
{
	exit('success');
}

/**
 * Path constants and validation
 */

// We're already in the app/ folder, so let's use that as the starting point.
defined('CRAFT_APP_PATH') || define('CRAFT_APP_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/')));

// The app/ folder goes inside craft/ by default, so work backwards from app/
defined('CRAFT_BASE_PATH')         || define('CRAFT_BASE_PATH',         CRAFT_APP_PATH.'../');

// Everything else should be relative from craft/ by default
defined('CRAFT_CONFIG_PATH')       || define('CRAFT_CONFIG_PATH',       CRAFT_BASE_PATH.'config/');
defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');
defined('CRAFT_STORAGE_PATH')      || define('CRAFT_STORAGE_PATH',      CRAFT_BASE_PATH.'storage/');
defined('CRAFT_TEMPLATES_PATH')    || define('CRAFT_TEMPLATES_PATH',    CRAFT_BASE_PATH.'templates/');
defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', CRAFT_BASE_PATH.'translations/');

function craft_createFolder($path)
{
	// Code borrowed from IOHelper...
	if (!is_dir($path))
	{
		$oldumask = umask(0);

		if (!mkdir($path, 0755, true))
		{
			exit('Tried to create a folder at '.$path.', but could not.');
		}

		// Because setting permission with mkdir is a crapshoot.
		chmod($path, 0755);
		umask($oldumask);
	}
}

function craft_ensureFolderIsReadable($path, $writableToo = false)
{
	$path = realpath($path);

	// !@file_exists('/.') is a workaround for the terrible is_executable()
	if ($path === false || !is_dir($path) || (!is_writable($path)) || !@file_exists($path.'/.'))
	{
		exit (($path !== false ? $path : $path).' doesn\'t exist or isn\'t writable by PHP. Please fix that.');
	}
}

// Validate permissions on craft/config/ and craft/storage/
craft_ensureFolderIsReadable(CRAFT_CONFIG_PATH);
craft_ensureFolderIsReadable(CRAFT_STORAGE_PATH, true);

// Create the craft/storage/runtime/ folder if it doesn't already exist
craft_createFolder(CRAFT_STORAGE_PATH.'runtime/');
craft_ensureFolderIsReadable(CRAFT_STORAGE_PATH.'runtime/', true);

/**
 * Load the config
 */

// Set the environment
defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', $_SERVER['SERVER_NAME']);

// Load the config early so we can set YII_DEBUG based on Dev Mode before loading Yii
$commonConfig = require CRAFT_APP_PATH.'etc/config/common.php';

/**
 * Load Yii, Composer dependencies, and the app
 */

// Load Yii, if it's not already
if (!class_exists('Yii', false))
{
	defined('YII_DEBUG') || define('YII_DEBUG', !empty($commonConfig['components']['config']['generalConfig']['devMode']));
	defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);
	require CRAFT_APP_PATH.'framework/yii.php';
}

// Load up Composer's files
require CRAFT_APP_PATH.'vendor/autoload.php';

// Disable the PHP include path
Yii::$enableIncludePath = false;

// Load 'em up
require CRAFT_APP_PATH.'Craft.php';
require CRAFT_APP_PATH.'etc/web/WebApp.php';
require CRAFT_APP_PATH.'Info.php';

// Set some aliases for Craft::import()
Yii::setPathOfAlias('app', CRAFT_APP_PATH);
Yii::setPathOfAlias('plugins', CRAFT_PLUGINS_PATH);

// Load the full config
$config = require CRAFT_APP_PATH.'etc/config/main.php';

// Initialize Craft\WebApp this way so it doesn't cause a syntax error on PHP < 5.3
$appClass = '\Craft\WebApp';
$app = new $appClass($config);

$app->run();
