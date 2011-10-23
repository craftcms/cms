<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'business'.DIRECTORY_SEPARATOR.'enums'.DIRECTORY_SEPARATOR.'DatabaseType.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'site.php');
require_once(BLOCKS_CONFIG_PATH.'db.php');

Yii::setPathOfAlias('common', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'common');

// validate configs
function generateConnectionString($dbConfig)
{
	return strtolower($dbConfig['type']).':host='.$dbConfig['server'].';dbname='.$dbConfig['name'].';port='.$dbConfig['port'];
}

return array(
	'basePath'          => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'              => 'Blocks',

	'preload' => array('log'),

	// autoloading model and component classes
	'import' => array(
		'application.models.*',
		'application.components.*',
		'application.controllers.*',
		'application.business.*',
		'application.business.enums.*',
		'application.business.web.*',
		'application.business.web.filters.*',
		'application.business.repositories.*',
		'application.migrations.*',
		'common.business.*',
		'common.business.enums.*',
		'common.business.exceptions.*',
		'common.business.utils.*',
		'common.business.web.*',
		'common.business.web.httpclient.*',
		'common.business.web.httpclient.hostnames.*',
		'common.business.web.httpclient.adapter.*',
		'common.business.web.filters.*',
		'common.business.webservices.*',
	),

	'modules' => array(
		'gii' => array(
			'class'     => 'system.gii.GiiModule',
			'password'  => 'letmein',
			//'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'assets',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			//'ipFilters' => array('127.0.0.1', '::1'),
		),
		'install',
		'update',
	),

	// application components
	'components' => array(
		'user' => array(
			// enable cookie-based authentication
			'allowAutoLogin' => true,
		),

		'pluginRepo' => array(
			'class' => 'application.business.repositories.PluginRepository',
		),

		'coreRepo' => array(
			'class' => 'application.business.repositories.CoreRepository',
		),

		'configRepo' => array(
			'class' => 'application.business.repositories.ConfigRepository',
		),

		'userRepo' => array(
			'class' => 'application.business.repositories.UserRepository',
		),

		'file' => array(
			'class' => 'common.business.utils.CFile'
		),

		'templateCPCache' => array(
			'class' => 'CTemplateFileCache',
			'cachePath' => BLOCKS_RUNTIME_PATH.'cached'.DIRECTORY_SEPARATOR.'translated_cp_templates',
		),

		'templateSiteCache' => array(
			'class' => 'CTemplateFileCache',
			'cachePath' => BLOCKS_RUNTIME_PATH.'cached'.DIRECTORY_SEPARATOR.'translated_site_templates',
		),

		'urlManager' => array(
			'class' => 'application.business.web.CmsUrlManager',
			'urlFormat' => 'path',
			'rules' => array(
				//'<controller:\w+>/<id:\d+>' => '<controller>/view',
				//'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
				//'<controller:\w+>/<action:\w+>' => '<controller>/<action>',
				'system/install/<action:\w+>' => 'install/default/<action>',
				'system/update/<action:\w+>' => 'update/default/<action>',
				///'error' => 'site/error',
			),
		),

		'db' => array(
			'connectionString'  => generateConnectionString($dbConfig),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => $dbConfig['tablePrefix'],
		),

		//'assetManager' => array(
		//    'class' => 'application.business.BlocksAssetManager',
		//),

		'assetManager' => array(
			'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'assets',
			'baseUrl' => '../../system/app/blocks/assets',
		),

		'errorHandler' => array(
			// use 'site/error' action to display errors
			'errorAction' => 'site/error',
		),

		'log' => array(
			'class' => 'CLogRouter',
			'routes' => array(
				array(
					'class' => 'CFileLogRoute',
					'levels' => 'error, warning',
				),
				array(
					'class'=>'CWebLogRoute',
				),
			),
		),

		'request' => array(
			'class' => 'common.business.web.BlocksHttpRequest',
		),

	),

	'params' => array(
		// this is used in contact page
		'adminEmail' => 'brad@pixelandtonic.com',
		'databaseConfig' => $dbConfig,
		'siteConfig' => $siteConfig

	),
);
