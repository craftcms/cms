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
			'class' => 'Blocks\AssetService',
		),

		'content' => array(
			'class' => 'Blocks\ContentService',
		),

		'cp' => array(
			'class' => 'Blocks\CpService',
		),

		'dashboard' => array(
			'class' => 'Blocks\DashboardService',
		),

		'email' => array(
			'class' => 'Blocks\EmailService',
		),

		'et' => array(
			'class' => 'Blocks\EtService',
		),

		'installer' => array(
			'class' => 'Blocks\InstallerService',
		),

		'path' => array(
			'class' => 'Blocks\PathService',
		),

		'plugins' => array(
			'class' => 'Blocks\PluginService',
		),

		'security' => array(
			'class' => 'Blocks\SecurityService',
		),

		'settings' => array(
			'class' => 'Blocks\SettingsService',
		),

		'site' => array(
			'class' => 'Blocks\SiteService',
		),

		'update' => array(
			'class' => 'Blocks\UpdateService',
		),

		'users' => array(
			'class' => 'Blocks\UsersService',
		),

		// end services

		'file' => array(
			'class' => 'Blocks\File',
		),

		'request' => array(
			'class' => 'Blocks\HttpRequest',
			'enableCookieValidation'      => true,
		),

		'viewRenderer' => array(
			'class' => 'Blocks\TemplateRenderer',
		),

		'urlManager' => array(
			'class' => 'Blocks\UrlManager',
			'routePatterns' => array(
				'{wild}'    => '.+',
				'{segment}' => '[^\/]*',
				'{number}'  => '\d+',
				'{word}'    => '[A-Za-z]\w*',
			),
			'cpRoutes' => array(
				array('update/({segment})',             'update', array('handle')),
				array('settings/sites/new',             'settings/sites/edit'),
				array('settings/sites/edit/({number})', 'settings/sites/edit', array('siteId')),
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
			'driverMap'         => array('mysql' => 'Blocks\MysqlSchema'),
			'class'             => 'Blocks\DbConnection'
		),

		'assetManager' => array(
			'basePath' => dirname(__FILE__).'/../assets',
			'baseUrl' => '../../blocks/app/assets',
		),

		'errorHandler' => array(
			// use 'site/error' action to display errors
			//'errorAction' => 'site/error',
			'class' => 'Blocks\ErrorHandler'
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
			'class'         => 'Blocks\HttpSession',
			'sessionName'   => 'BlocksSessionId',
		),

		'user' => array(
			'class'             => 'Blocks\WebUser',
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
