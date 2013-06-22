<?php

// Make sure this is PHP 5.3 or later
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('@@@appName@@@ requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
}

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

// Define app constants
defined('CRAFT_BASE_PATH')         || define('CRAFT_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../')).'/');
defined('CRAFT_APP_PATH')          || define('CRAFT_APP_PATH',          CRAFT_BASE_PATH.'app/');
defined('CRAFT_CONFIG_PATH')       || define('CRAFT_CONFIG_PATH',       CRAFT_BASE_PATH.'config/');
defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');
defined('CRAFT_STORAGE_PATH')      || define('CRAFT_STORAGE_PATH',      CRAFT_BASE_PATH.'storage/');
defined('CRAFT_TEMPLATES_PATH')    || define('CRAFT_TEMPLATES_PATH',    CRAFT_BASE_PATH.'templates/');
defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', CRAFT_BASE_PATH.'translations/');
defined('YII_TRACE_LEVEL')         || define('YII_TRACE_LEVEL', 3);

// Not using is_executable here, but it's worthless.
// Check early if storage/ is a valid folder, writable and executable.
if (($storagePath = realpath(CRAFT_STORAGE_PATH)) === false || !is_dir($storagePath) || !is_writable($storagePath) || !@file_exists($storagePath.'/.'))
{
	exit('@@@appName@@@ storage path "'.($storagePath === false ? CRAFT_STORAGE_PATH : $storagePath).'" isn&rsquo;t valid. Please make sure it is a folder writable by your web server process.');
}

// Create the runtime path if it doesn't exist already
// (code borrowed from IOHelper)
$runtimePath = CRAFT_STORAGE_PATH.'runtime/';
if (!is_dir($runtimePath))
{
	$oldumask = umask(0);

	if (!mkdir($runtimePath, 0755, true))
	{
		exit('Tried to create a folder at '.$runtimePath.', but could not.');
	}

	// Because setting permission with mkdir is a crapshoot.
	chmod($runtimePath, 0755);
	umask($oldumask);
}

// Check early if storage/runtime is a valid folder and writable. !@file_exists('/.') is a workaround for the terrible is_executable().
if (($runtimePath = realpath(CRAFT_STORAGE_PATH.'runtime/')) === false || !is_dir($runtimePath) || !is_writable($runtimePath) || !@file_exists($runtimePath.'/.'))
{
	exit('@@@appName@@@ runtime path "'.($runtimePath === false ? CRAFT_STORAGE_PATH.'runtime/' : $runtimePath).'" isn&rsquo;t valid. Please make sure it is a folder writable by your web server process.');
}

// Check early if config is a valid folder and writable. !@file_exists('/.') is a workaround for the terrible is_executable().
if (($siteConfigPath = realpath(CRAFT_CONFIG_PATH)) === false || !is_dir($siteConfigPath) || !@file_exists($siteConfigPath.'/.'))
{
	exit('@@@appName@@@ config path "'.($siteConfigPath === false ? CRAFT_CONFIG_PATH : $siteConfigPath).'" isn&rsquo;t valid. Please make sure the folder exists and is readable by your web server process.');
}

$userConfig = require_once CRAFT_CONFIG_PATH.'general.php';

// Set YII_DEBUG to true if we're in devMode.
if (isset($userConfig['devMode']) && $userConfig['devMode'] == true)
{
	define('YII_DEBUG', true);
}

// In case yiic is running
if (!class_exists('Yii', false))
{
	require_once CRAFT_APP_PATH.'framework/yii.php';
}

// Disable the PHP include path
Yii::$enableIncludePath = false;

// Load 'em up
require_once CRAFT_APP_PATH.'Craft.php';
require_once CRAFT_APP_PATH.'etc/web/WebApp.php';
require_once CRAFT_APP_PATH.'Info.php';

$configPath = CRAFT_APP_PATH.'etc/config/main.php';

// Initialize Craft\WebApp this way so it doesn't cause a syntax error on PHP < 5.3
$appClass = '\Craft\WebApp';
$app = new $appClass($configPath);

$app->run();
