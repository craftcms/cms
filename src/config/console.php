<?php

$common = require(CRAFT_APP_PATH.'etc/config/common.php');

return CMap::mergeArray($common, [

	'basePath' => dirname(__FILE__).'/../../',

	// autoloading model and component classes
	'import' => [
		'application.*',
		'application.migrations.*',
	],

	'componentAliases' => [
		'app.*',
		'app.enums.*',
		'app.etc.components.*',
		'app.etc.console.*',
		'app.etc.console.commands.*',
		'app.etc.dates.*',
		'app.etc.db.*',
		'app.etc.db.schemas.*',
		'app.etc.io.*',
		'app.etc.logging.*',
		'app.etc.updates.*',
		'app.helpers.*',
		'app.migrations.*',
		'app.services.*',
		'app.validators.*',
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
]);
