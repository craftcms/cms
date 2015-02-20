<?php

return [
	// Non-configured components
	'assets'               => 'craft\app\services\Assets',
	'assetIndexing'        => 'craft\app\services\AssetIndexing',
	'assetSources'         => 'craft\app\services\AssetSources',
	'assetTransforms'      => 'craft\app\services\AssetTransforms',
	'categories'           => 'craft\app\services\Categories',
	'config'               => 'craft\app\services\Config',
	'content'              => 'craft\app\services\Content',
	//'coreMessages'         => 'Craft\PhpMessageSource',
	'dashboard'            => 'craft\app\services\Dashboard',
	'deprecator'           => 'craft\app\services\Deprecator',
	'elements'             => 'craft\app\services\Elements',
	'email'                => 'craft\app\services\Email',
	'entries'              => 'craft\app\services\Entries',
	'et'                   => 'craft\app\services\Et',
	'feeds'                => 'craft\app\services\Feeds',
	'fields'               => 'craft\app\services\Fields',
	'fileCache'            => 'craft\app\cache\FileCache',
	'globals'              => 'craft\app\services\Globals',
	'i18n'                 => 'craft\app\i18n\I18N',
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
	'session'              => 'craft\app\web\Session',
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
			'assetSource'   => ['subfolder' => 'assetsourcetypes', 'namespace' => '\craft\app\assetsourcetypes', 'instanceof' => '\craft\app\assetsourcetypes\BaseAssetSourceType',  'enableForPlugins' => false],
			'element'       => ['subfolder' => 'elementtypes',     'namespace' => '\craft\app\elementtypes',     'instanceof' => '\craft\app\elementtypes\ElementTypeInterface',     'enableForPlugins' => true],
			'elementAction' => ['subfolder' => 'elementactions',   'namespace' => '\craft\app\elementactions',   'instanceof' => '\craft\app\elementactions\ElementActionInterface', 'enableForPlugins' => true],
			'field'         => ['subfolder' => 'fieldtypes',       'namespace' => '\craft\app\fieldtypes',       'instanceof' => '\craft\app\fieldtypes\FieldTypeInterface',         'enableForPlugins' => true],
			'tool'          => ['subfolder' => 'tools',            'namespace' => '\craft\app\tools',            'instanceof' => '\craft\app\tools\ToolInterface',                   'enableForPlugins' => false],
			'task'          => ['subfolder' => 'tasks',            'namespace' => '\craft\app\tasks',            'instanceof' => '\craft\app\tasks\TaskInterface',                   'enableForPlugins' => true],
			'widget'        => ['subfolder' => 'widgets',          'namespace' => '\craft\app\widgets',          'instanceof' => '\craft\app\widgets\WidgetInterface',               'enableForPlugins' => true],
		]
	],
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
	'urlManager' => [
		'class' => 'craft\app\web\UrlManager',
		'enablePrettyUrl' => true,
		'ruleConfig' => ['class' => 'craft\app\web\UrlRule'],
	],
	'user' => [
		'class'                    => 'craft\app\web\User',
		'identityClass'            => 'craft\app\models\User',
		'enableAutoLogin'          => true,
		'autoRenewCookie'          => true,
	],
];
