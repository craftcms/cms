<?php

$common = require_once(BLOCKS_APP_PATH.'config/common.php');

Yii::setPathOfAlias('app', BLOCKS_APP_PATH);
Yii::setPathOfAlias('plugins', BLOCKS_PLUGINS_PATH);

return CMap::mergeArray(
	$common,

	array(
		'basePath'    => BLOCKS_APP_PATH,
		'runtimePath' => BLOCKS_RUNTIME_PATH,
		'name'        => 'Blocks',

		// autoloading model and component classes
		'import' => array(
			'application.lib.*',
			'application.lib.PhpMailer.*',
			'application.lib.Requests.*',
			'application.lib.Requests.Auth.*',
			'application.lib.Requests.Response.*',
			'application.lib.Requests.Transport.*',
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

			'components' => array(
				'class' => 'Blocks\ComponentsService',
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

			'systemSettings' => array(
				'class' => 'Blocks\SystemSettingsService',
			),

			'templates' => array(
				'class' => 'Blocks\TemplatesService',
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
				'class' => 'Blocks\HttpRequestService',
				'enableCookieValidation' => true,
			),

			'viewRenderer' => array(
				'class' => 'Blocks\TemplateProcessor',
			),

			'statePersister' => array(
				'stateFile' => BLOCKS_RUNTIME_PATH.'state/state.bin',
			),

			'urlManager' => array(
				'class' => 'Blocks\UrlManager',
				'cpRoutes' => array(
					/* BLOCKS ONLY */
					'content\/blog\/new'                                               => 'content/blog/_edit',
					'content\/blog\/(?P<entryId>\d+)'                                  => 'content/blog/_edit',
					/* end BLOCKS ONLY */
					'dashboard\/settings\/new'                                         => 'dashboard/settings/_widgetsettings',
					'dashboard\/settings\/(?P<widgetId>\d+)'                           => 'dashboard/settings/_widgetsettings',
					'update\/(?P<handle>[^\/]*)'                                       => 'update',
					/* BLOCKSPRO ONLY */
					'users\/new'                                                       => 'users/_edit/account',
					'users\/(?P<userId>\d+)'                                           => 'users/_edit/account',
					'users\/(?P<userId>\d+)\/profile'                                  => 'users/_edit/profile',
					'users\/(?P<userId>\d+)\/admin'                                    => 'users/_edit/admin',
					'users\/(?P<userId>\d+)\/info'                                     => 'users/_edit/info',
					/* end BLOCKSPRO ONLY */
					'content\/(?P<entryId>\d+)'                                        => 'content/_entry',
					'content\/(?P<entryId>\d+)\/draft(?P<draftNum>\d+)'                => 'content/_entry',
					'plugins\/(?P<pluginClass>[A-Za-z]\w*)'                            => 'plugins/_settings',
					/* BLOCKS ONLY */
					'settings\/blog\/blocks\/new'                                      => 'settings/blog/_blocksettings',
					'settings\/blog\/blocks\/(?P<blockId>\d+)'                         => 'settings/blog/_blocksettings',
					/* end BLOCKS ONLY */
					/* BLOCKSPRO ONLY */
					'settings\/sections\/new'                                          => 'settings/sections/_settings',
					'settings\/sections\/(?P<sectionId>\d+)'                           => 'settings/sections/_settings',
					'settings\/sections\/(?P<sectionId>\d+)\/blocks'                   => 'settings/sections/_blocks',
					'settings\/sections\/(?P<sectionId>\d+)\/blocks\/new'              => 'settings/sections/_blocks/settings',
					'settings\/sections\/(?P<sectionId>\d+)\/blocks\/(?P<blockId>\d+)' => 'settings/sections/_blocks/settings',
					/* end BLOCKSPRO ONLY */
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
				'class'         => 'Blocks\HttpSessionService',
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
