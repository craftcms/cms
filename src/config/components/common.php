<?php

return [
	// Non-configured components
	'assets'               => 'craft\app\services\Assets',
	'assetIndexing'        => 'craft\app\services\AssetIndexing',
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
	'volumes'              => 'craft\app\services\Volumes',


	// Configured components
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
		'identityClass'            => 'craft\app\elements\User',
		'enableAutoLogin'          => true,
		'autoRenewCookie'          => true,
	],
];
