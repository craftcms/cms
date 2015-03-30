<?php

return [
	'components' => [
		'request' => 'craft\app\console\Request',
		'user' => 'craft\app\console\User',
	],
	'controllerMap' => [
		'migrate' => 'craft\app\console\controllers\MigrateController',
	]
];
