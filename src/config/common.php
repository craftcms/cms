<?php

require_once(BLOCKS_APP_PATH.'config/defaults/blocks.php');
require_once(BLOCKS_APP_PATH.'config/defaults/db.php');
require_once(BLOCKS_CONFIG_PATH.'blocks.php');
require_once(BLOCKS_CONFIG_PATH.'db.php');

/**
 * @param $dbHostname
 * @return string
 */
function normalizeDbHostname($dbHostname)
{
	// *nix command line db connections want this in numeric format.
	if (strcasecmp($dbHostname, 'localhost') == 0)
		$dbHostname = '127.0.0.1';

	return $dbHostname;
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
			'connectionString'  => strtolower('mysql:host='.normalizeDbHostname($dbConfig['server']).';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => rtrim($dbConfig['tablePrefix'], '_').'_',
			'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
			'class'             => 'Blocks\DbConnection',
		),

		'config' => array(
			'class' => 'Blocks\ConfigService',
		),
	),

	'params' => array(
		'adminEmail'            => 'brad@pixelandtonic.com',
		'dbConfig'              => $dbConfig,
		'blocksConfig'          => $blocksConfig,
	)
);
