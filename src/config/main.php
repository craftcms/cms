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

		// application components
		'components' => array(
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

			'sites' => array(
				'class' => 'Blocks\SitesService',
			),

			'updates' => array(
				'class' => 'Blocks\UpdatesService',
			),

			'users' => array(
				'class' => 'Blocks\UsersService',
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
				'class' => 'Blocks\FileTemplateProcessor',
			),

			'statePersister' => array(
				'class' => 'Blocks\StatePersister'
			),

			'urlManager' => array(
				'class' => 'Blocks\UrlManager',
				'routePatterns' => array(
					'{wild}'    => '.+',
					'{segment}' => '[^\/]*',
					'{number}'  => '\d+',
					'{handle}'  => '[A-Za-z]\w*'
				),
				'cpRoutes' => array(
					array('update/({segment})',                      'update', array('handle')),
					array('users/new',                               'users/_edit'),
					array('users/edit/({number})',                   'users/_edit', array('userId')),
					array('settings/blocks/new',                     'settings/blocks/_edit'),
					array('settings/blocks/edit/({number})',         'settings/blocks/_edit', array('blockId')),
					array('content/({number})',                      'content/_entry', array('entryId')),
					array('content/({number})/draft({number})',      'content/_entry', array('entryId', 'draftNum')),
					array('content/sections/new',                    'content/_section'),
					array('content/sections/({number})',             'content/_section', array('sectionId')),
					array('plugins/({handle})',                      'plugins/_settings', array('pluginClass')),
					array('settings/sites/new',                      'settings/_site'),
					array('settings/sites/({number})',               'settings/_site', array('siteId')),
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
