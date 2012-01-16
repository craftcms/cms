<?php

defined('BLOCKS_BASE_PATH') || define('BLOCKS_BASE_PATH', realpath(dirname(__FILE__).'/../').'/');
defined('BLOCKS_CP_REQUEST') || define('BLOCKS_CP_REQUEST', false);

// change the following paths if necessary
$framework = BLOCKS_BASE_PATH.'app/framework/yii.php';
$framework_config = BLOCKS_BASE_PATH.'app/config/main.php';

// remove the following lines when in production mode
defined('YII_DEBUG') || define('YII_DEBUG', true);
error_reporting(E_ALL);

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);

// In case yiic is running
if(!class_exists('Yii'))
	require_once($framework);

require_once(BLOCKS_BASE_PATH.'app/business/web/BlocksApp.php');

$app = new BlocksApp($framework_config);
$app->run();
