<?php

return [
	'components' => [
		'request' => [
			'class' => 'craft\app\web\Request',
			'enableCookieValidation' => true,
		],
		'response' => 'craft\app\web\Response',
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
	]
];
