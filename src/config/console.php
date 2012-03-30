<?php
require_once(dirname(__FILE__).'/defaults/db.php');
require_once(dirname(__FILE__).'/../../config/db.php');

return array(
	'basePath' => dirname(__FILE__).'/..',

	// autoloading model and component classes
	'import' => array(
		'application.business.*',
		'application.business.Blocks',
		'application.business.services.*',
		'application.migrations.*',
		'application.framework.cli.commands.*',
		'application.framework.logging.CLogger',
	),

	// application components
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
		),

		'config' => array(
			'class' => 'Blocks\ConfigService',
		),
	),

	'commandPath' => dirname(__FILE__).'/../business/console/commands',

	'params' => array(
		'dbConfig' => $dbConfig,
	),
);
