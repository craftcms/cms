<?php

return [

	'basePath' => dirname(__FILE__).'/../../',

	// autoloading model and component classes
	'import' => [
		'application.*',
		'application.migrations.*',
	],

	'components' => [
		'db' => [
			'emulatePrepare'    => true,
			'driverMap'         => ['mysql' => 'Craft\MysqlSchema'],
			'class'             => 'Craft\DbConnection',
		],
		'migrations' => [
			'class'             => 'Craft\MigrationsService',
		],
	],

	'commandPath' => craft\app\Craft::getAlias('system.cli.commands.*'),
];
