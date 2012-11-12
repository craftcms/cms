<?php

// Make sure this is PHP 5.3 or later
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('Blocks requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
}

// Is this a PATH_INFO test?
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testpathinfo')
{
	exit('success');
}

// Define app constants
defined('BLOCKS_BASE_PATH')         || define('BLOCKS_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../')).'/');
defined('BLOCKS_APP_PATH')          || define('BLOCKS_APP_PATH',          BLOCKS_BASE_PATH.'app/');
defined('BLOCKS_CONFIG_PATH')       || define('BLOCKS_CONFIG_PATH',       BLOCKS_BASE_PATH.'config/');
defined('BLOCKS_PLUGINS_PATH')      || define('BLOCKS_PLUGINS_PATH',      BLOCKS_BASE_PATH.'plugins/');
defined('BLOCKS_RUNTIME_PATH')      || define('BLOCKS_RUNTIME_PATH',      BLOCKS_BASE_PATH.'runtime/');
defined('BLOCKS_UPLOADS_PATH')      || define('BLOCKS_UPLOADS_PATH',      BLOCKS_RUNTIME_PATH.'uploads/');
defined('BLOCKS_TEMPLATES_PATH')    || define('BLOCKS_TEMPLATES_PATH',    BLOCKS_BASE_PATH.'templates/');
defined('BLOCKS_TRANSLATIONS_PATH') || define('BLOCKS_TRANSLATIONS_PATH', BLOCKS_BASE_PATH.'translations/');
defined('BLOCKS_CP_REQUEST')        || define('BLOCKS_CP_REQUEST', false);
defined('YII_TRACE_LEVEL')          || define('YII_TRACE_LEVEL', 3);

// Check early if runtime is a valid folder and writable.
if (($runtimePath = realpath(BLOCKS_RUNTIME_PATH)) === false || !is_dir($runtimePath) || !is_writable($runtimePath))
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
$app = new Blocks\App($config);
$app->run();
