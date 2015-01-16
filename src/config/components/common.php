<?php

return [
	// Non-configured components
	'assets'               => 'craft\app\services\Assets',
	'assetIndexing'        => 'craft\app\services\AssetIndexing',
	'assetSources'         => 'craft\app\services\AssetSources',
	'assetTransforms'      => 'craft\app\services\AssetTransforms',
	'cache'                => 'craft\app\services\Cache',
	'categories'           => 'craft\app\services\Categories',
	'config'               => 'craft\app\services\Config',
	'content'              => 'craft\app\services\Content',
	//'coreMessages'         => 'Craft\PhpMessageSource',
	'dashboard'            => 'craft\app\services\Dashboard',
	'db'                   => 'craft\app\db\DbConnection',
	'deprecator'           => 'craft\app\services\Deprecator',
	'elements'             => 'craft\app\services\Elements',
	'email'                => 'craft\app\services\Email',
	'entries'              => 'craft\app\services\Entries',
	'et'                   => 'craft\app\services\Et',
	'feeds'                => 'craft\app\services\Feeds',
	'fields'               => 'craft\app\services\Fields',
	'fileCache'            => 'craft\app\cache\FileCache',
	'globals'              => 'craft\app\services\Globals',
	'i18n'                 => 'craft\app\services\Localization',
	'install'              => 'craft\app\services\Install',
	'images'               => 'craft\app\services\Images',
	'matrix'               => 'craft\app\services\Matrix',
	'messages'             => 'Craft\PhpMessageSource',
	'migrations'           => 'craft\app\services\Migrations',
	'path'                 => 'craft\app\services\Path',
	'plugins'              => 'craft\app\services\Plugins',
	'relations'            => 'craft\app\services\Relations',
	'routes'               => 'craft\app\services\Routes',
	'search'               => 'craft\app\services\Search',
	'security'             => 'craft\app\services\Security',
	'session'              => 'craft\app\services\Session',
	'structures'           => 'craft\app\services\Structures',
	'tags'                 => 'craft\app\services\Tags',
	'tasks'                => 'craft\app\services\Tasks',
	'templateCache'        => 'craft\app\services\TemplateCache',
	'templates'            => 'craft\app\services\Templates',
	'tokens'               => 'craft\app\services\Tokens',
	'updates'              => 'craft\app\services\Updates',
	'users'                => 'craft\app\services\Users',

	// Configured components
	'components' => [
		'class' => 'craft\app\services\Components',
		'types' => [
			'assetSource'   => ['subfolder' => 'assetsourcetypes', 'suffix' => 'AssetSourceType', 'instanceof' => 'BaseAssetSourceType',    'enableForPlugins' => false],
			'element'       => ['subfolder' => 'elementtypes',     'suffix' => 'ElementType',     'instanceof' => 'ElementTypeInterface',   'enableForPlugins' => true],
			'elementAction' => ['subfolder' => 'elementactions',   'suffix' => 'ElementAction',   'instanceof' => 'ElementActionInterface', 'enableForPlugins' => true],
			'field'         => ['subfolder' => 'fieldtypes',       'suffix' => 'FieldType',       'instanceof' => 'FieldTypeInterface',     'enableForPlugins' => true],
			'tool'          => ['subfolder' => 'tools',            'suffix' => 'Tool',            'instanceof' => 'ToolInterface',          'enableForPlugins' => false],
			'task'          => ['subfolder' => 'tasks',            'suffix' => 'Task',            'instanceof' => 'TaskInterface',          'enableForPlugins' => true],
			'widget'        => ['subfolder' => 'widgets',          'suffix' => 'Widget',          'instanceof' => 'WidgetInterface',        'enableForPlugins' => true],
		]
	],
	//'errorHandler' => [
	//	'class'       => 'craft\app\errors\ErrorHandler',
	//	'errorAction' => 'templates/renderError'
	//],
	'resources' => [
		'class'     => 'craft\app\services\Resources',
		'dateParam' => 'd',
	],
	'sections' => [
		'class' => 'craft\app\services\Sections',
		'typeLimits' => [
			'single'    => 5,
			'channel'   => 1,
			'structure' => 0
		]
	],
	'systemSettings' => [
		'class' => 'craft\app\services\SystemSettings',
		'defaults' => [
			'users' => [
				'requireEmailVerification' => true,
				'allowPublicRegistration' => false,
				'defaultGroup' => null,
			],
			'email' => [
				'emailAddress' => null,
				'senderName' => null,
				'template' => null,
				'protocol' => null,
				'username' => null,
				'password' => null,
				'port' => 25,
				'host' => null,
				'timeout' => 30,
				'smtpKeepAlive' => false,
				'smtpAuth' => false,
				'smtpSecureTransportType' => 'none',
			]
		]
	],
	//'urlManager' => [
	//	'class'     => 'craft\app\web\UrlManager',
	//	'routeParam' => 'p',
	//],
	'user' => [
		'class'                    => 'craft\app\web\User',
		'identityClass'            => 'craft\app\models\User',
		'enableAutoLogin'          => true,
		'autoRenewCookie'          => true,
	],
];
