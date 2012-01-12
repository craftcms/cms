<?php
require_once(BLOCKS_BASE_PATH.'config/blocks.php');
require_once(BLOCKS_BASE_PATH.'config/db.php');

if (!isset($db['port']))
	$db['port'] = '3306';

if (!isset($db['charset']))
	$db['charset'] = 'utf8';

if (!isset($db['collation']))
	$db['collation'] = 'utf8_unicode_ci';

if (!isset($db['type']))
	$db['type'] = 'mysql';

$db['database'] = $db['database'].'_test';

return CMap::mergeArray(
	require(dirname(__FILE__) . '/main.php'),
	array(
		'components' => array(
			'fixture' => array(
				'class' => 'system.test.CDbFixtureManager',
			),

			'db' => array(
				'connectionString'  => strtolower($db['type'].':host='.$db['server'].';dbname='.$db['database'].';port='.$db['port'].';'),
				// emulatePrepare => true recommended if using PHP 5.1.3 or higher
				'emulatePrepare'    => true,
				'username'          => $db['user'],
				'password'          => $db['password'],
				'charset'           => $db['charset'],
				'tablePrefix'       => rtrim($db['tablePrefix'], '_').'_',
				'driverMap'         => getDbDriverMap(),
			),
		),

		'params' => array(
				// this is used in contact page
				'adminEmail' => 'brad@pixelandtonic.com',
				'db' => $db,
				'config' => $blocksConfig,
		),
	)
);
