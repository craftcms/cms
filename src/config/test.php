<?php
require_once(dirname(__FILE__).'/../business/Defines.php');

return CMap::mergeArray(
	require(dirname(__FILE__) . '/main.php'),
	array(
		'components' => array(
			'fixture' => array(
				'class' => 'system.test.CDbFixtureManager',
			),

			'db' => array(
				'connectionString'  => 'mysql:host=127.0.0.1;dbname=blocks_test',
				'emulatePrepare'    => true,
				'username'          => 'root',
				'password'          => 'letmein',
				'charset'           => 'utf8',
				'tablePrefix'       => 'blx',
			),
		),
	)
);
