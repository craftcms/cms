<?php

$common = require_once(BLOCKS_APP_PATH.'config/common.php');

Yii::setPathOfAlias('base', BLOCKS_BASE_PATH);
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
			'application.business.lib.Requests.*',
			'application.business.lib.Requests.Auth.*',
			'application.business.lib.Requests.Response.*',
			'application.business.lib.Requests.Transport.*',
		),

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

			'request' => array(
				'class' => 'Blocks\HttpRequest',
				'enableCookieValidation'      => true,
			),

			'viewRenderer' => array(
				'class' => 'Blocks\TemplateRenderer',
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
					'{word}'    => '[A-Za-z]\w*',
				),
				'cpRoutes' => array(
					array('update/({segment})',                      'update', array('handle')),
					array('users/new',                               'users/_edit'),
					array('users/edit/({number})',                   'users/_edit', array('userId')),
					array('users/view/({number})',                   'users/view', array('userId')),
					array('settings/blocks/new',                     'settings/blocks/_edit'),
					array('settings/blocks/edit/({number})',         'settings/blocks/_edit', array('blockId')),
					array('settings/plugins/({word})',               'settings/plugins/_edit', array('pluginShortName')),
					array('content/edit/({number})',                 'content/_edit', array('entryId')),
					array('content/edit/({number})/draft({number})', 'content/_edit', array('entryId', 'draftNum')),
					array('settings/sections/new',                   'settings/sections/_edit'),
					array('settings/sections/edit/({number})',       'settings/sections/_edit', array('sectionId')),
					array('settings/sites/new',                      'settings/sites/_edit'),
					array('settings/sites/edit/({number})',          'settings/sites/_edit', array('siteId')),
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
				'class' => 'CLogRouter',
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
					)
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

