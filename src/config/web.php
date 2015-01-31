<?php

return [
	'components' => [
		'request' => [
			'class' => 'craft\app\web\Request',
			'enableCookieValidation' => true,
		],
		'errorHandler' => [
			'errorAction' => 'templates/renderError'
		]
	]
];
