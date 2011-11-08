<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'business'.DIRECTORY_SEPARATOR.'enums'.DIRECTORY_SEPARATOR.'DatabaseType.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'blocks.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'db.php');

Yii::setPathOfAlias('base', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

if (!isset($db['port']))
	$db['port'] = '3306';

if (!isset($db['charset']))
	$db['charset'] = 'utf8';

if (!isset($db['collation']))
	$db['collation'] = 'utf8_unicode_ci';

if (!isset($db['type']))
	$db['type'] = DatabaseType::MySQL;

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
		'application.business.services.*',
		'application.business.tags.*',
		'application.business.tags.abstract.*',
		'application.business.tags.assets.*',
		'application.business.tags.content.*',
		'application.business.tags.cp.*',
		'application.business.tags.membership.*',
		'application.business.tags.primitive.*',
		'application.business.tags.security.*',
		'application.business.tags.site.*',
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

		'plugins' => array(
			'class' => 'application.business.services.PluginService',
		),

		'core' => array(
			'class' => 'application.business.services.CoreService',
		),

		'config' => array(
			'class' => 'application.business.services.ConfigService',
		),

		'content' => array(
			'class' => 'application.business.services.ContentService',
		),

		'membership' => array(
			'class' => 'application.business.services.MembershipService',
		),

		'security' => array(
			'class' => 'application.business.services.SecurityService',
		),

		'assets' => array(
			'class' => 'application.business.services.AssetService',
		),

		'file' => array(
			'class' => 'application.business.utils.BlocksFile'
		),

//		'templateCache' => array(
//			'class' => 'application.business.web.TemplateFileCache',
//		),

		'viewRenderer' => array(
			'class' => 'application.business.web.BlocksViewRenderer',
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
			'connectionString'  => strtolower($db['type'].':host='.$db['server'].';dbname='.$db['database'].';port='.$db['port'].';'),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $db['user'],
			'password'          => $db['password'],
			'charset'           => $db['charset'],
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
