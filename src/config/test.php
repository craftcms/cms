<?php

$main = require_once(CRAFT_APP_PATH.'etc/config/main.php');

return \craft\app\helpers\ArrayHelper::merge(
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
