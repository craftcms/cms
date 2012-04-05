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

return CMap::mergeArray(
	require(BLOCKS_APP_PATH.'config/common.php'),

	array(
		'basePath' => dirname(__FILE__).'/..',

		// autoloading model and component classes
		'import' => array(
			'application.business.*',
			'application.business.Blocks',
			'application.business.services.*',
			'application.migrations.*',
		),

		'components' => array(

			'db' => array(
				'connectionString'  => strtolower('mysql:host='.normalizeDbHostname($dbConfig['server']).';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
				'emulatePrepare'    => true,
				'username'          => $dbConfig['user'],
				'password'          => $dbConfig['password'],
				'charset'           => $dbConfig['charset'],
				'tablePrefix'       => rtrim($dbConfig['tablePrefix'], '_').'_',
				'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
				'class'             => 'Blocks\DbConnection',
			),
		),

		'commandPath' => dirname(__FILE__).'/../business/console/commands',
	)
);
