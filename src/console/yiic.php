<?php

$config = dirname(__FILE__).'/../../config/console.php';

defined('BLOCKS_BASE_PATH')      || define('BLOCKS_BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../../../')).'/');
defined('BLOCKS_APP_PATH')       || define('BLOCKS_APP_PATH', BLOCKS_BASE_PATH.'app/');
defined('BLOCKS_CONFIG_PATH')    || define('BLOCKS_CONFIG_PATH', BLOCKS_BASE_PATH.'config/');

/**
 * Yii command line script file configured for Blocks.
 */

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

require_once(dirname(__FILE__).'/../../framework/yii.php');
require_once(BLOCKS_APP_PATH.'Blocks.php');
require_once(dirname(__FILE__).'/ConsoleApplication.php');

$app = Yii::createApplication('Blocks\ConsoleApplication', $config);
$app->commandRunner->addCommands(Blocks\Blocks::getPathOfAlias('application.console.commands.*'));

$app->run();
