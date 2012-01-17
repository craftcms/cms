<?php

require_once(BLOCKS_BASE_PATH.'app/business/db/DriverMap.php');
require_once(BLOCKS_BASE_PATH.'app/config/defaults/blocks.php');
require_once(BLOCKS_BASE_PATH.'app/config/defaults/db.php');
require_once(BLOCKS_BASE_PATH.'config/blocks.php');
require_once(BLOCKS_BASE_PATH.'config/db.php');

Yii::setPathOfAlias('base', BLOCKS_BASE_PATH);

if ($blocksConfig['devMode'] == true)
	$blocksConfig['cacheTimeSeconds'] = $blocksConfig['devCacheTimeSeconds'];

return array(
	'basePath'    => BLOCKS_BASE_PATH.'app/',
	'runtimePath' => Yii::getPathOfAlias('base.runtime'),
	'name'        => 'Blocks',

	'preload'     => array('log'),

	// autoloading model and component classes
	'import' => array(
		'application.business.*',
		'application.business.db.*',
		'application.business.enums.*',
		'application.business.exceptions.*',
		'application.business.install.*',
		'application.business.services.*',
		'application.business.updates.*',
		'application.business.utils.*',
		'application.business.web.*',
		'application.business.web.filters.*',
		'application.business.web.httpclient.*',
		'application.business.web.httpclient.adapter.*',
		'application.business.web.httpclient.hostnames.*',
		'application.business.web.templatewidgets.*',
		'application.business.webservices.*',
		'application.controllers.*',
		'application.migrations.*',
		'application.models.*',
		'application.models.forms.*',
		'application.widgets.*',
		'application.tags.*',
		'application.tags._primitive.*',
		'application.tags.assets.*',
		'application.tags.content.*',
		'application.tags.cp.*',
		'application.tags.users.*',
		'application.tags.security.*',
		'application.tags.site.*',
	),

	'modules' => array(
	),

	// application components
	'components' => array(
		// services
		'assets' => array(
			'class' => 'application.business.services.AssetService',
		),

		'content' => array(
			'class' => 'application.business.services.ContentService',
		),

		'cp' => array(
			'class' => 'application.business.services.CpService',
		),

		'et' => array(
			'class' => 'application.business.services.ETService',
		),

		'path' => array(
			'class' => 'application.business.services.PathService',
		),

		'plugins' => array(
			'class' => 'application.business.services.PluginService',
		),

		'security' => array(
			'class' => 'application.business.services.SecurityService',
		),

		'site' => array(
			'class' => 'application.business.services.SiteService',
		),

		'update' => array(
					'class' => 'application.business.services.UpdateService',
		),

		'users' => array(
			'class' => 'application.business.services.UsersService',
		),

		// end services

		'file' => array(
			'class' => 'application.business.utils.BlocksFile',
		),

		'request' => array(
			'class' => 'application.business.web.BlocksHttpRequest',
			'enableCookieValidation'      => true,
		),

		'viewRenderer' => array(
			'class' => 'application.business.web.BlocksTemplateRenderer',
		),

		'urlManager' => array(
			'class' => 'application.business.web.BlocksUrlManager',
			'rules' => array(
				//'<controller:\w+>/<id:\d+>' => '<controller>/view',
				//'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
				//'<controller:\w+>/<action:\w+>' => '<controller>/<action>',
				//'install/<action:\w+>' => 'install/default/<action>',
				//'update/<pluginHandle:\w+>' =>
				//'system/update/<action:\w+>' => 'update/default/<action>',
				//'admin' => 'admin.php',
			),
		),

		'db' => array(
			'connectionString'  => strtolower('mysql:host='.$db['server'].';dbname='.$db['database'].';port='.$db['port'].';'),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $db['user'],
			'password'          => $db['password'],
			'charset'           => $db['charset'],
			'tablePrefix'       => rtrim($db['tablePrefix'], '_').'_',
			'driverMap'         => getDbDriverMap(),
			'class'             => 'application.business.db.BlocksDbConnection'
		),

		'assetManager' => array(
			'basePath' => dirname(__FILE__).'/../assets',
			'baseUrl' => '../../blocks/app/assets',
		),

		'errorHandler' => array(
			// use 'site/error' action to display errors
			//'errorAction' => 'site/error',
			'class' => 'application.business.web.BlocksErrorHandler'
		),

		'fileCache' => array(
			'class' => 'CFileCache',
		),

		'log' => array(
			'class' => 'CLogRouter',
			'routes' => array(
				array(
					'class'  => 'CFileLogRoute',
					'levels' => 'error, warning',
				),
				//array(
				//	'class' => 'application.business.logging.BlocksWebLogRoute',
				//),
			),
		),

		'session' => array(
			'autoStart'     => true,
			'cookieMode'    => 'only',
			'class'         => 'application.business.web.BlocksHttpSession',
			'sessionName'   => 'BlocksSessionId',
		),

		'user' => array(
			'class'             => 'application.business.web.BlocksWebUser',
			'allowAutoLogin'    => true,
			'loginUrl'          => array('/login'),
			'autoRenewCookie'   => true,
		),
	),

	'params' => array(
		// this is used in contact page
		'adminEmail' => 'brad@pixelandtonic.com',
		'db' => $db,
		'config' => $blocksConfig,
		'requiredPhpVersion' => '5.1.0',
		'requiredMysqlVersion' => ''
	),
);
