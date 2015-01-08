<?php

$main = require_once(CRAFT_APP_PATH.'etc/config/main.php');

return CMap::mergeArray(
	$main,

	[
		'components' => [
			'fixture' => [
				'class' => 'system.test.CDbFixtureManager',
			],
			'request'
		],
	]
);
