<?php

// Is this a PATH_INFO test?
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testpathinfo')
{
	exit('success');
}

// Define path constants
defined('BLOCKS_BASE_PATH')              || define('BLOCKS_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../')).'/');
defined('BLOCKS_APP_PATH')               || define('BLOCKS_APP_PATH',               BLOCKS_BASE_PATH.'app/');
defined('BLOCKS_CONFIG_PATH')            || define('BLOCKS_CONFIG_PATH',            BLOCKS_BASE_PATH.'config/');
defined('BLOCKS_PLUGINS_PATH')           || define('BLOCKS_PLUGINS_PATH',           BLOCKS_BASE_PATH.'plugins/');
defined('BLOCKS_RUNTIME_PATH')           || define('BLOCKS_RUNTIME_PATH',           BLOCKS_BASE_PATH.'runtime/');
defined('BLOCKS_SITE_TEMPLATES_PATH')    || define('BLOCKS_SITE_TEMPLATES_PATH',    BLOCKS_BASE_PATH.'templates/');
defined('BLOCKS_SITE_TRANSLATIONS_PATH') || define('BLOCKS_SITE_TRANSLATIONS_PATH', BLOCKS_BASE_PATH.'translations/');

defined('BLOCKS_CP_REQUEST') || define('BLOCKS_CP_REQUEST', false);

// Check early if runtime is a valid folder and writable.
if (($runtimePath = realpath(BLOCKS_RUNTIME_PATH)) === false || !is_dir($runtimePath) || !is_writable($runtimePath))
	exit('@@@productDisplay@@@ runtime path "'.$runtimePath.'" isn’t valid. Please make sure it is a folder writable by your web server process.');

// change the following paths if necessary
$framework = BLOCKS_APP_PATH.'framework/yii.php';
$config    = BLOCKS_APP_PATH.'config/main.php';

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);

// In case yiic is running
if(!class_exists('Yii'))
	require_once($framework);

require_once BLOCKS_APP_PATH.'business/Blocks.php';
require_once BLOCKS_APP_PATH.'business/web/App.php';
$app = new Blocks\App($config);
$app->run();
