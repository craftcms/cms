<?php

/**
 * @param $dbHostname
 * @return string
 */
function normalizeDbHostname($dbHostname)
{
	// MacOS command line db connections apparently want this in numeric format.
	if (strcasecmp($dbHostname, 'localhost') == 0)
		$dbHostname = '127.0.0.1';

	return $dbHostname;
}

// Table prefixes cannot be longer than 5 characters
$tablePrefix = rtrim($dbConfig['tablePrefix'], '_');
if ($tablePrefix)
{
	if (strlen($tablePrefix) > 5)
		$tablePrefix = substr($tablePrefix, 0, 5);
	$tablePrefix .= '_';
}

return CMap::mergeArray(
	require(BLOCKS_APP_PATH.'config/common.php'),

	array(
		'basePath' => dirname(__FILE__).'/../',

		// autoloading model and component classes
		'import' => array(
			'application.*',
			'application.migrations.*',
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
		),

		'commandPath' => Blocks\Blocks::getPathOfAlias('system.cli.commands.*'),
	)
);
