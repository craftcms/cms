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
$common = require(BLOCKS_APP_PATH.'config/common.php');

return CMap::mergeArray($common, array(

	'basePath' => dirname(__FILE__).'/../',

	// autoloading model and component classes
	'import' => array(
		'application.*',
		'application.migrations.*',
	),

	'componentAliases' => array(
			'app.*',
			'app.console.*',
			'app.console.commands.*',
			'app.components.datetime.*',
			'app.components.db.*',
			'app.components.email.*',
			'app.components.enums.*',
			'app.components.io.helpers.*',
			'app.components.io.interfaces.*',
			'app.components.io.*',
			'app.components.logging.*',
			'app.components.updates.*',
			'app.components.core.helpers.*',
			'app.components.core.validators.*',
			'app.components.updates.services.*',
			'app.migrations.*',
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
