<?php

// Make sure this is PHP 5.3 or later
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('Blocks requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
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
defined('BLOCKS_BASE_PATH')         || define('BLOCKS_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../')).'/');
defined('BLOCKS_APP_PATH')          || define('BLOCKS_APP_PATH',          BLOCKS_BASE_PATH.'app/');
defined('BLOCKS_CONFIG_PATH')       || define('BLOCKS_CONFIG_PATH',       BLOCKS_BASE_PATH.'config/');
defined('BLOCKS_PLUGINS_PATH')      || define('BLOCKS_PLUGINS_PATH',      BLOCKS_BASE_PATH.'plugins/');
defined('BLOCKS_STORAGE_PATH')      || define('BLOCKS_STORAGE_PATH',      BLOCKS_BASE_PATH.'storage/');
defined('BLOCKS_TEMPLATES_PATH')    || define('BLOCKS_TEMPLATES_PATH',    BLOCKS_BASE_PATH.'templates/');
defined('BLOCKS_TRANSLATIONS_PATH') || define('BLOCKS_TRANSLATIONS_PATH', BLOCKS_BASE_PATH.'translations/');
defined('YII_TRACE_LEVEL')          || define('YII_TRACE_LEVEL', 3);

// Check early if storage/ is a valid folder and writable.
if (($storagePath = realpath(BLOCKS_STORAGE_PATH)) === false || !is_dir($storagePath) || !is_writable($storagePath))
{
	exit('Blocks storage path "'.$storagePath.'" isn&rsquo;t valid. Please make sure it is a folder writable by your web server process.');
}

// Create the runtime path if it doesn't exist already
// (code borrowed from IOHelper)
$runtimePath = BLOCKS_STORAGE_PATH.'runtime/';
if (!is_dir($runtimePath))
{
	$oldumask = umask(0);

	if (!mkdir($runtimePath, 0754, true))
	{
		exit('Tried to create a folder at '.$runtimePath.', but could not.');
	}

	// Because setting permission with mkdir is a crapshoot.
	chmod($runtimePath, 0754);
	umask($oldumask);
}

// Check early if storage/runtime is a valid folder and writable.
if (($runtimePath = realpath(BLOCKS_STORAGE_PATH.'runtime/')) === false || !is_dir($runtimePath) || !is_writable($runtimePath))
{
	exit('Blocks runtime path "'.$runtimePath.'" isn&rsquo;t valid. Please make sure it is a folder writable by your web server process.');
}

// In case yiic is running
if (!class_exists('Yii', false))
{
	require_once BLOCKS_APP_PATH.'framework/yii.php';
}

// Disable the PHP include path
Yii::$enableIncludePath = false;

// Load 'em up
require_once BLOCKS_APP_PATH.'Blocks.php';
require_once BLOCKS_APP_PATH.'App.php';
require_once BLOCKS_APP_PATH.'Info.php';

$config = require_once BLOCKS_APP_PATH.'config/main.php';

// Initialize Blocks\App this way so it doesn't cause a syntax error on PHP < 5.3
$appClass = '\Blocks\App';
$app = new $appClass($config);

$app->run();
