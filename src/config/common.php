<?php

require_once(BLOCKS_APP_PATH.'config/defaults/blocks.php');
require_once(BLOCKS_APP_PATH.'config/defaults/db.php');
require_once(BLOCKS_CONFIG_PATH.'blocks.php');
require_once(BLOCKS_CONFIG_PATH.'db.php');

if ($blocksConfig['devMode'] == true)
{
	defined('YII_DEBUG') || define('YII_DEBUG', true);
	error_reporting(E_ALL & ~E_STRICT);
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	ini_set('error_log', BLOCKS_STORAGE_PATH.'logs/phperrors.log');
}
else
{
	error_reporting(0);
	ini_set('display_errors', 0);
}

// Table prefixes cannot be longer than 5 characters
$tablePrefix = rtrim($dbConfig['tablePrefix'], '_');
if ($tablePrefix)
{
	if (strlen($tablePrefix) > 5)
	{
		$tablePrefix = substr($tablePrefix, 0, 5);
	}

	$tablePrefix .= '_';
}

$configArray = array(

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

if (in_array('Rebrand', $packages))
{
	$configArray['componentAliases'] = array_merge($configArray['componentAliases'], array(
/* REBRAND COMPONENT ALIASES */
	));
}

if (in_array('PublishPro', $packages))
{
	$configArray['componentAliases'] = array_merge($configArray['componentAliases'], array(
/* PUBLISHPRO COMPONENT ALIASES */
	));
}

if (in_array('Cloud', $packages))
{
	$configArray['componentAliases'] = array_merge($configArray['componentAliases'], array(
/* CLOUD COMPONENT ALIASES */
	));
}

if (in_array('Language', $packages))
{
	$configArray['componentAliases'] = array_merge($configArray['componentAliases'], array(
/* LANGUAGE COMPONENT ALIASES */
	));
}

if (in_array('Users', $packages))
{
	$configArray['componentAliases'] = array_merge($configArray['componentAliases'], array(
/* USERS COMPONENT ALIASES */
	));
}

return $configArray;
