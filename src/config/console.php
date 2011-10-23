<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'business'.DIRECTORY_SEPARATOR.'enums'.DIRECTORY_SEPARATOR.'DatabaseType.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'db.php');

Yii::setPathOfAlias('common', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'common');

function generateConnectionString($dbConfig)
{
	return strtolower($dbConfig['type']).':host='.$dbConfig['server'].';dbname='.$dbConfig['name'];
}

return array(
	'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',

	// autoloading model and component classes
	'import' => array(
		'application.business.*',
		'application.business.repositories.*',
		'application.migrations.*',
		'common.business.*',
	),

	// application components
	'components' => array(

		'db' => array(
			'connectionString'  => generateConnectionString($dbConfig),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $dbConfig['tablePrefix'],
		),

		'configRepo' => array(
			'class' => 'application.business.repositories.ConfigRepository',
		),
	),

	// application-level parameters that can be accessed
	// using Blocks::app()->params['paramName']
	'params' => array(
		'databaseConfig' => $dbConfig,
	),
);
