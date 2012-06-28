<?php

require_once(BLOCKS_APP_PATH.'config/defaults/blocks.php');
require_once(BLOCKS_APP_PATH.'config/defaults/db.php');
require_once(BLOCKS_CONFIG_PATH.'blocks.php');
require_once(BLOCKS_CONFIG_PATH.'db.php');

if ($blocksConfig['devMode'] == true)
{
	defined('YII_DEBUG') || define('YII_DEBUG', true);
	error_reporting(E_ALL);

	$blocksConfig['cacheTimeSeconds'] = $blocksConfig['devCacheTimeSeconds'];
}

return array(

	// autoloading model and component classes
	'import' => array(
		'application.framework.cli.commands.*',
		'application.framework.console.*',
		'application.framework.logging.CLogger',
	),

	'components' => array(

		'db' => array(
			'connectionString'  => strtolower('mysql:host='.$dbConfig['server'].';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => rtrim($dbConfig['tablePrefix'], '_').'_',
			'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
			'class'             => 'Blocks\DbConnection',
			'pdoClass'          => 'Blocks\PDO',
		),

		'config' => array(
			'class' => 'Blocks\ConfigService',
		),

		'localization' => array(
			'class' => 'Blocks\LocalizationService',
		)
	),

	'params' => array(
		'adminEmail'            => 'brad@pixelandtonic.com',
		'dbConfig'              => $dbConfig,
		'blocksConfig'          => $blocksConfig,
	)
);
