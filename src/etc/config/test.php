<?php

$main = require_once(BLOCKS_APP_PATH.'etc/config/main.php');

$dbConfig['database'] = $dbConfig['database'].'_test';

return CMap::mergeArray(
	$main,

	array(
		'components' => array(
			'fixture' => array(
				'class' => 'system.test.CDbFixtureManager',
			),
			'request'
		),
	)
);
