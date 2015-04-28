<?php

return [
	'components' => [
		'request' => [
			'class' => 'craft\app\web\Request',
			'enableCookieValidation' => true,
		],
		'response' => 'craft\app\web\Response',
		'session'  => 'craft\app\web\Session',
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
		'errorHandler' => [
			'errorAction' => 'templates/render-error'
		]
	],
	'modules' => [
		'debug' => [
			'class' => 'yii\debug\Module',
			'panels' => [
				'config' => false,
				'info' => ['class' => 'craft\app\debug\InfoPanel'],
				'request' => ['class' => 'yii\debug\panels\RequestPanel'],
				'log' => ['class' => 'yii\debug\panels\LogPanel'],
				'deprecated' => ['class' => 'craft\app\debug\DeprecatedPanel'],
				'profiling' => ['class' => 'yii\debug\panels\ProfilingPanel'],
				'db' => ['class' => 'yii\debug\panels\DbPanel'],
				'assets' => ['class' => 'yii\debug\panels\AssetPanel'],
				'mail' => ['class' => 'yii\debug\panels\MailPanel'],
			]
		]
	],
];
