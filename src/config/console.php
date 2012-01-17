<?php
require_once(dirname(__FILE__).'/defaults/db.php');
require_once(dirname(__FILE__).'/../../config/db.php');

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
			'connectionString'  => strtolower('mysql:host='.$db['server'].';dbname='.$db['database'].';port='.$db['port'].';'),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $db['user'],
			'password'          => $db['password'],
			'charset'           => $db['charset'],
			'tablePrefix'       => $db['tablePrefix'],
		),
	),

	// application-level parameters that can be accessed
	// using Blocks::app()->params['paramName']
	'params' => array(
		'db' => $db,
	),
);
