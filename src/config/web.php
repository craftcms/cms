<?php

return [
	'components' => [
		'request' => [
			'class' => 'craft\app\web\Request',
			'enableCookieValidation' => true,
		],
		'response' => 'craft\app\web\Response',
		'errorHandler' => [
			'errorAction' => 'templates/render-error'
		]
	]
];
