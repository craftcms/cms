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

// Table prefixes cannot be longer than 5 characters
$tablePrefix = rtrim($dbConfig['tablePrefix'], '_');
if ($tablePrefix)
{
	if (strlen($tablePrefix) > 5)
		$tablePrefix = substr($tablePrefix, 0, 5);
	$tablePrefix .= '_';
}

return array(

	// autoloading model and component classes
	'import' => array(
		'application.framework.cli.commands.*',
		'application.framework.console.*',
		'application.framework.logging.CLogger',
	),

	'componentAliases' => array(
/* COMPONENT ALIASES */
	),

	'components' => array(

		'db' => array(
			'connectionString'  => strtolower('mysql:host='.$dbConfig['server'].';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $tablePrefix,
			'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
			'class'             => 'Blocks\DbConnection',
			'pdoClass'          => 'Blocks\PDO',
		),

		'config' => array(
			'class' => 'Blocks\ConfigService',
		),

		'i18n' => array(
			'class' => 'Blocks\LocalizationService',
		)
	),

	'params' => array(
		'adminEmail'            => 'brad@pixelandtonic.com',
		'dbConfig'              => $dbConfig,
		'blocksConfig'          => $blocksConfig,
	)
);
