<?php
require_once(BLOCKS_BASE_PATH.'app/config/defaults/blocks.php');
require_once(BLOCKS_BASE_PATH.'app/config/defaults/db.php');
require_once(BLOCKS_BASE_PATH.'config/blocks.php');
require_once(BLOCKS_BASE_PATH.'config/db.php');

$dbConfig['database'] = $dbConfig['database'].'_test';

return CMap::mergeArray(
	require(dirname(__FILE__) . '/main.php'),
	array(
		'components' => array(
			'fixture' => array(
				'class' => 'system.test.CDbFixtureManager',
			),

			'db' => array(
				'connectionString'  => strtolower('mysql:host='.$dbConfig['server'].';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
				// emulatePrepare => true recommended if using PHP 5.1.3 or higher
				'emulatePrepare'    => true,
				'username'          => $dbConfig['user'],
				'password'          => $dbConfig['password'],
				'charset'           => $dbConfig['charset'],
				'tablePrefix'       => rtrim($dbConfig['tablePrefix'], '_').'_',
				'driverMap'         => array('mysql' => 'bMysqlSchema'),
			),
		),

		'params' => array(
				// this is used in contact page
				'adminEmail'   => 'brad@pixelandtonic.com',
				'dbConfig'     => $dbConfig,
				'blocksConfig' => $blocksConfig,
		),
	)
);
