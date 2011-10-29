<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'business'.DIRECTORY_SEPARATOR.'enums'.DIRECTORY_SEPARATOR.'DatabaseType.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'db.php');

function generateConnectionString($db)
{
	return strtolower($db['type']).':host='.$db['server'].';dbname='.$db['name'];
}

return array(
	'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',

	// autoloading model and component classes
	'import' => array(
		'application.business.*',
		'application.business.repositories.*',
		'application.migrations.*',
	),

	// application components
	'components' => array(

		'db' => array(
			'connectionString'  => generateConnectionString($db),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $db['user'],
			'password'          => $db['password'],
			'charset'           => $db['charset'],
			'tablePrefix'       => $db['tablePrefix'],
		),

		'configRepo' => array(
			'class' => 'application.business.repositories.ConfigRepository',
		),
	),

	// application-level parameters that can be accessed
	// using Blocks::app()->params['paramName']
	'params' => array(
		'db' => $db,
	),
);
