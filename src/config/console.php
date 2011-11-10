<?php
require_once(dirname(__FILE__).'/../business/enums/DatabaseType.php');
require_once(dirname(__FILE__).'/../../config/db.php');

if (!isset($db['port']))
	$db['port'] = '3306';

if (!isset($db['charset']))
	$db['charset'] = 'utf8';

if (!isset($db['collation']))
	$db['collation'] = 'utf8_unicode_ci';

if (!isset($db['type']))
	$db['type'] = DatabaseType::MySQL;

return array(
	'basePath' => dirname(__FILE__).'/..',

	// autoloading model and component classes
	'import' => array(
		'application.business.*',
		'application.business.services.*',
		'application.migrations.*',
	),

	// application components
	'components' => array(

		'db' => array(
			'connectionString'  => strtolower($db['type'].':host='.$db['server'].';dbname='.$db['database'].';port='.$db['port'].';'),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $db['user'],
			'password'          => $db['password'],
			'charset'           => $db['charset'],
			'tablePrefix'       => $db['tablePrefix'],
		),

		'config' => array(
			'class' => 'application.business.services.ConfigService',
		),
	),

	// application-level parameters that can be accessed
	// using Blocks::app()->params['paramName']
	'params' => array(
		'db' => $db,
	),
);
