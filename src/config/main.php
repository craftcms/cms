<?php

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
		'application.business.lib.*',
		'application.business.lib.httpclient.*',
		'application.business.lib.httpclient.adapter.*',
		'application.business.lib.httpclient.hostnames.*',
		'application.business.services.*',
		'application.business.updates.*',
		'application.business.utils.*',
		'application.business.web.*',
		'application.business.web.filters.*',
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
			'class' => 'application.business.services.bAssetService',
		),

		'content' => array(
			'class' => 'application.business.services.bContentService',
		),

		'cp' => array(
			'class' => 'application.business.services.bCpService',
		),

		'email' => array(
			'class' => 'application.business.services.bEmailService',
		),

		'et' => array(
			'class' => 'application.business.services.BEtService',
		),

		'path' => array(
			'class' => 'application.business.services.bPathService',
		),

		'plugins' => array(
			'class' => 'application.business.services.bPluginService',
		),

		'security' => array(
			'class' => 'application.business.services.bSecurityService',
		),

		'site' => array(
			'class' => 'application.business.services.bSiteService',
		),

		'update' => array(
					'class' => 'application.business.services.bUpdateService',
		),

		'users' => array(
			'class' => 'application.business.services.bUsersService',
		),

		// end services

		'file' => array(
			'class' => 'application.business.utils.bFile',
		),

		'request' => array(
			'class' => 'application.business.web.bHttpRequest',
			'enableCookieValidation'      => true,
		),

		'viewRenderer' => array(
			'class' => 'application.business.web.bTemplateRenderer',
		),

		'urlManager' => array(
			'class' => 'application.business.web.bUrlManager',
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
			'connectionString'  => strtolower('mysql:host='.$dbConfig['server'].';dbname='.$dbConfig['database'].';port='.$dbConfig['port'].';'),
			// emulatePrepare => true recommended if using PHP 5.1.3 or higher
			'emulatePrepare'    => true,
			'username'          => $dbConfig['user'],
			'password'          => $dbConfig['password'],
			'charset'           => $dbConfig['charset'],
			'tablePrefix'       => rtrim($dbConfig['tablePrefix'], '_').'_',
			'driverMap'         => array('mysql' => 'bMysqlSchema'),
			'class'             => 'application.business.db.bDbConnection'
		),

		'assetManager' => array(
			'basePath' => dirname(__FILE__).'/../assets',
			'baseUrl' => '../../blocks/app/assets',
		),

		'errorHandler' => array(
			// use 'site/error' action to display errors
			//'errorAction' => 'site/error',
			'class' => 'application.business.web.bErrorHandler'
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
				//	'class' => 'application.business.logging.bWebLogRoute',
				//),
			),
		),

		'session' => array(
			'autoStart'     => true,
			'cookieMode'    => 'only',
			'class'         => 'application.business.web.bHttpSession',
			'sessionName'   => 'BlocksSessionId',
		),

		'user' => array(
			'class'             => 'application.business.web.bWebUser',
			'allowAutoLogin'    => true,
			'loginUrl'          => array('/login'),
			'autoRenewCookie'   => true,
		),
	),

	'params' => array(
		// this is used in contact page
		'adminEmail'           => 'brad@pixelandtonic.com',
		'dbConfig'             => $dbConfig,
		'blocksConfig'         => $blocksConfig,
		'requiredPhpVersion'   => '5.1.0',
		'requiredMysqlVersion' => ''
	),
);
