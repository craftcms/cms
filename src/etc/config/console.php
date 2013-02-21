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

$packages = explode(',', BLOCKS_PACKAGES);
$common = require(BLOCKS_APP_PATH.'etc/config/common.php');

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
			'connectionString'  => strtolower('mysql:host='.normalizeDbHostname($dbConfig['server']).';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $tablePrefix,
			'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
			'class'             => 'Blocks\DbConnection',
		),
		'migrations' => array(
			'class'             => 'Blocks\MigrationsService',
		),
	),

	'commandPath' => Blocks\Blocks::getPathOfAlias('system.cli.commands.*'),
));
