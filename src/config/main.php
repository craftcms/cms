<?php

require_once(BLOCKS_APP_PATH.'config/defaults/blocks.php');
require_once(BLOCKS_APP_PATH.'config/defaults/db.php');
require_once(BLOCKS_CONFIG_PATH.'blocks.php');
require_once(BLOCKS_CONFIG_PATH.'db.php');

Yii::setPathOfAlias('base', BLOCKS_BASE_PATH);
Yii::setPathOfAlias('app', BLOCKS_APP_PATH);
Yii::setPathOfAlias('config', BLOCKS_CONFIG_PATH);
Yii::setPathOfAlias('plugins', BLOCKS_PLUGINS_PATH);
Yii::setPathOfAlias('runtime', BLOCKS_RUNTIME_PATH);
Yii::setPathOfAlias('templates', BLOCKS_TEMPLATES_PATH);

if ($blocksConfig['devMode'] == true)
	$blocksConfig['cacheTimeSeconds'] = $blocksConfig['devCacheTimeSeconds'];

return array(
	'basePath'    => BLOCKS_APP_PATH,
	'runtimePath' => BLOCKS_RUNTIME_PATH,
	'name'        => 'Blocks',

	'preload'     => array('log'),

	// autoloading model and component classes
	'import' => array(
		'application.business.lib.*',
		'application.business.lib.httpclient.*',
		'application.business.lib.httpclient.adapter.*',
		'application.business.lib.httpclient.hostnames.*',
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

		'email' => array(
			'class' => 'application.business.services.EmailService',
		),

		'et' => array(
			'class' => 'application.business.services.EtService',
		),

		'installer' => array(
			'class' => 'application.business.services.InstallerService',
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

		'settings' => array(
			'class' => 'application.business.services.SettingsService',
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
			'class' => 'application.business.utils.File',
		),

		'request' => array(
			'class' => 'Blocks\HttpRequest',
			'enableCookieValidation'      => true,
		),

		'viewRenderer' => array(
			'class' => 'application.business.web.TemplateRenderer',
		),

		'urlManager' => array(
			'class' => 'application.business.web.UrlManager',
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
			'driverMap'         => array('mysql' => 'MysqlSchema'),
			'class'             => 'application.business.db.DbConnection'
		),

		'assetManager' => array(
			'basePath' => dirname(__FILE__).'/../assets',
			'baseUrl' => '../../blocks/app/assets',
		),

		'errorHandler' => array(
			// use 'site/error' action to display errors
			//'errorAction' => 'site/error',
			'class' => 'application.business.web.ErrorHandler'
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
				//	'class' => 'application.business.logging.WebLogRoute',
				//),
			),
		),

		'session' => array(
			'autoStart'     => true,
			'cookieMode'    => 'only',
			'class'         => 'application.business.web.HttpSession',
			'sessionName'   => 'BlocksSessionId',
		),

		'user' => array(
			'class'             => 'application.business.web.WebUser',
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
