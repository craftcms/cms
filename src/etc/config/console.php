<?php

/**
 * @param $dbHostname
 * @return string
 */
function normalizeDbHostname($dbHostname)
{
	// MacOS command line db connections apparently want this in numeric format.
	if (strcasecmp($dbHostname, 'localhost') == 0)
	{
		$dbHostname = '127.0.0.1';
	}

	return $dbHostname;
}

/**
 * Returns the correct connection string depending on whether a unixSocket is specific or not in the db config.
 *
 * @param $dbConfig
 * @return string
 */
function processConnectionString($dbConfig)
{
	if (!empty($dbConfig['unixSocket']))
	{
		return strtolower('mysql:unix_socket='.$dbConfig['unixSocket'].';dbname='.$dbConfig['database'].';');
	}
	else
	{
		return strtolower('mysql:host='.normalizeDbHostname($dbConfig['server']).';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';');
	}
}

$common = require(CRAFT_APP_PATH.'etc/config/common.php');

return CMap::mergeArray($common, array(

	'basePath' => dirname(__FILE__).'/../../',

	// autoloading model and component classes
	'import' => array(
		'application.*',
		'application.migrations.*',
	),

	'componentAliases' => array(
		'app.*',
		'app.enums.*',
		'app.etc.components.*',
		'app.etc.console.*',
		'app.etc.console.commands.*',
		'app.etc.dates.*',
		'app.etc.db.*',
		'app.etc.db.schemas.*',
		'app.etc.io.*',
		'app.etc.logging.*',
		'app.etc.updates.*',
		'app.helpers.*',
		'app.migrations.*',
		'app.services.*',
		'app.validators.*',
	),

	'components' => array(
		'db' => array(
			'connectionString'  => processConnectionString($dbConfig),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $tablePrefix,
			'driverMap'         => array('mysql' => 'Craft\MysqlSchema'),
			'class'             => 'Craft\DbConnection',
		),
		'migrations' => array(
			'class'             => 'Craft\MigrationsService',
		),
	),

	'commandPath' => Craft\Craft::getPathOfAlias('system.cli.commands.*'),
));
