<?php

// Is this a PATH_INFO test?
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testpathinfo')
{
	exit('success');
}

// Define path constants
defined('BLOCKS_BASE_PATH')      || define('BLOCKS_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../')).'/');
defined('BLOCKS_APP_PATH')       || define('BLOCKS_APP_PATH',       BLOCKS_BASE_PATH.'app/');
defined('BLOCKS_CONFIG_PATH')    || define('BLOCKS_CONFIG_PATH',    BLOCKS_BASE_PATH.'config/');
defined('BLOCKS_PLUGINS_PATH')   || define('BLOCKS_PLUGINS_PATH',   BLOCKS_BASE_PATH.'plugins/');
defined('BLOCKS_RUNTIME_PATH')   || define('BLOCKS_RUNTIME_PATH',   BLOCKS_BASE_PATH.'runtime/');
defined('BLOCKS_TEMPLATES_PATH') || define('BLOCKS_TEMPLATES_PATH', BLOCKS_BASE_PATH.'templates/');

defined('BLOCKS_CP_REQUEST') || define('BLOCKS_CP_REQUEST', false);

// change the following paths if necessary
$framework = BLOCKS_APP_PATH.'framework/yii.php';
$config    = BLOCKS_APP_PATH.'config/main.php';

// remove the following lines when in production mode
defined('YII_DEBUG') || define('YII_DEBUG', true);
error_reporting(E_ALL);

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);


// In case yiic is running
if(!class_exists('Yii'))
	require_once($framework);

require_once(BLOCKS_APP_PATH.'business/web/App.php');
$app = new Blocks\App($config);
$app->run();
