<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'business'.DIRECTORY_SEPARATOR.'enums'.DIRECTORY_SEPARATOR.'DatabaseType.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'blocks.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'db.php');

Yii::setPathOfAlias('base', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

// validate configs
function generateConnectionString($db)
{
	// TODO: fix port and type.
	return strtolower('mysql:host='.$db['server'].';dbname='.$db['database'].';port=3306;');
}

return array(
	'basePath'          => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'runtimePath'       => Yii::getPathOfAlias('base.runtime'),
	'name'              => 'Blocks',

	'preload' => array('log'),

	// autoloading model and component classes
	'import' => array(
		'application.business.*',
		'application.business.enums.*',
		'application.business.exceptions.*',
		'application.business.repositories.*',
		'application.business.tags.*',
		'application.business.tags.abstract.*',
		'application.business.tags.content.*',
		'application.business.tags.primitive.*',
		'application.business.updates.*',
		'application.business.utils.*',
		'application.business.web.*',
		'application.business.web.filters.*',
		'application.business.web.httpclient.*',
		'application.business.web.httpclient.adapter.*',
		'application.business.web.httpclient.hostnames.*',
		'application.business.webservices.*',
		'application.controllers.*',
		'application.migrations.*',
		'application.models.*',
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

		'contentRepo' => array(
			'class' => 'application.business.repositories.ContentRepository',
		),

		'userRepo' => array(
			'class' => 'application.business.repositories.UserRepository',
		),

		'file' => array(
			'class' => 'application.business.utils.BlocksFile'
		),

		'templateCPCache' => array(
			'class' => 'CTemplateFileCache',
			'cachePath' => 'base.app.templates',
		),

		'templateSiteCache' => array(
			'class' => 'CTemplateFileCache',
			'cachePath' => 'base.templates',
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

		// TODO: fix charset here.
		'db' => array(
			'connectionString'  => generateConnectionString($db),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $db['user'],
			'password'          => $db['password'],
			'charset'           => 'utf8',
			'tablePrefix'       => $db['tablePrefix'],
		),

		//'assetManager' => array(
		//    'class' => 'application.business.BlocksAssetManager',
		//),

		'assetManager' => array(
			'basePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'assets',
			'baseUrl' => '../../blocks/app/assets',
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
			'class' => 'application.business.web.BlocksHttpRequest',
		),

	),

	'params' => array(
		// this is used in contact page
		'adminEmail' => 'brad@pixelandtonic.com',
		'db' => $db,
		'config' => $blocksConfig

	),
);
