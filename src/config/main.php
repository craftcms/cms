<?php

$common = require_once(BLOCKS_APP_PATH.'config/common.php');

Yii::setPathOfAlias('app', BLOCKS_APP_PATH);
Yii::setPathOfAlias('config', BLOCKS_CONFIG_PATH);
Yii::setPathOfAlias('plugins', BLOCKS_PLUGINS_PATH);
Yii::setPathOfAlias('runtime', BLOCKS_RUNTIME_PATH);
Yii::setPathOfAlias('templates', BLOCKS_TEMPLATES_PATH);

return CMap::mergeArray(
	$common,

	array(
		'basePath'    => BLOCKS_APP_PATH,
		'runtimePath' => BLOCKS_RUNTIME_PATH,
		'name'        => 'Blocks',

		// autoloading model and component classes
		'import' => array(
			'application.business.lib.*',
			'application.business.lib.PhpMailer.*',
			'application.business.lib.Requests.*',
			'application.business.lib.Requests.Auth.*',
			'application.business.lib.Requests.Response.*',
			'application.business.lib.Requests.Transport.*',
		),

		// application components
		'components' => array(
			'accounts' => array(
				'class' => 'Blocks\AccountsService',
			),

			// services
			'assets' => array(
				'class' => 'Blocks\AssetsService',
			),

			'blocks' => array(
				'class' => 'Blocks\BlocksService',
			),

			'content' => array(
				'class' => 'Blocks\ContentService',
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
				'class' => 'Blocks\InstallService',
			),

			'migrations' => array(
				'class' => 'Blocks\MigrationsService',
			),

			'path' => array(
				'class' => 'Blocks\PathService',
			),

			'plugins' => array(
				'class' => 'Blocks\PluginsService',
			),

			'security' => array(
				'class' => 'Blocks\SecurityService',
			),

			'settings' => array(
				'class' => 'Blocks\SettingsService',
			),

			'updates' => array(
				'class' => 'Blocks\UpdatesService',
			),
			// end services

			'file' => array(
				'class' => 'Blocks\File',
			),

			'messages' => array(
				'class' => 'Blocks\PhpMessageSource',
			),

			'request' => array(
				'class' => 'Blocks\HttpRequest',
				'enableCookieValidation'      => true,
			),

			'viewRenderer' => array(
				'class' => 'Blocks\TemplateProcessor',
			),

			'statePersister' => array(
				'class' => 'Blocks\StatePersister'
			),

			'urlManager' => array(
				'class' => 'Blocks\UrlManager',
				'cpRoutes' => array(
					array('update\/(?<handle>[^\/]*)',                       'update'),
					array('accounts\/new',                                   'accounts/_edit/account'),
					array('accounts\/(?<userId>\d+)',                        'accounts/_edit/account'),
					array('accounts\/(?<userId>\d+)\/profile',               'accounts/_edit/profile'),
					array('accounts\/(?<userId>\d+)\/admin',                 'accounts/_edit/admin'),
					array('accounts\/(?<userId>\d+)\/info',                  'accounts/_edit/info'),
					array('settings\/blocks\/new',                           'settings/blocks/_edit'),
					array('settings\/blocks\/edit\/(?<blockId>\d+)',         'settings/blocks/_edit'),
					array('content\/(?<entryId>\d+)',                        'content/_entry'),
					array('content\/(?<entryId>\d+)\/draft(?<draftNum>\d+)', 'content/_entry'),
					array('content\/sections\/new',                          'content/_section'),
					array('content\/sections\/(?<sectionId>\d+)',            'content/_section'),
					array('plugins\/(?<pluginClass>[A-Za-z]\w*)',            'plugins/_settings'),
				),
			),

			'assetManager' => array(
				'basePath' => dirname(__FILE__).'/../assets',
				'baseUrl' => '../../blocks/app/assets',
			),

			'errorHandler' => array(
				'class' => 'Blocks\ErrorHandler'
			),

			'fileCache' => array(
				'class' => 'CFileCache',
			),

			'log' => array(
				'class' => 'Blocks\LogRouter',
				'routes' => array(
					array(
						'class'  => 'Blocks\FileLogRoute',
					),
					array(
						'class'         => 'Blocks\WebLogRoute',
						'filter'        => 'CLogFilter',
						'showInFireBug' => true,
					),
					array(
						'class'         => 'Blocks\ProfileLogRoute',
						'showInFireBug' => true,
					),
				),
			),

			'routes' => array(
				'class' => 'Blocks\RoutesService'
			),

			'session' => array(
				'autoStart'     => true,
				'cookieMode'    => 'only',
				'class'         => 'Blocks\HttpSession',
				'sessionName'   => 'BlocksSessionId',
			),

			'user' => array(
				'class'             => 'Blocks\UserSessionService',
				'allowAutoLogin'    => true,
				'loginUrl'          => array('/login'),
				'autoRenewCookie'   => true,
			),
		),

		'params' => array(
			'blocksConfig'         => $blocksConfig,
			'requiredPhpVersion'   => '5.3.0',
			'requiredMysqlVersion' => ''
		),
	)
);
