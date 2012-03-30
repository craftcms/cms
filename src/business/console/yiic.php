<?php

$config = dirname(__FILE__).'/../../config/console.php';

defined('BLOCKS_BASE_PATH')      || define('BLOCKS_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../../../')).'/');
defined('BLOCKS_APP_PATH')       || define('BLOCKS_APP_PATH', BLOCKS_BASE_PATH.'app/');

/**
 * Yii command line script file configured for Blocks.
 */

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('YII_DEBUG') or define('YII_DEBUG', true);

require_once(dirname(__FILE__).'/../../framework/yii.php');
require_once(dirname(__FILE__).'/ConsoleApplication.php');

$app = Yii::createApplication('Blocks\ConsoleApplication', $config);
$env = @getenv('YII_CONSOLE_COMMANDS');

if (!empty($env))
	$app->commandRunner->addCommands($env);

$app->run();
