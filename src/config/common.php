<?php

$components = require __DIR__.'/components/common.php';

return [
	'import' => [
		'application.framework.cli.commands.*',
		'application.framework.console.*',
		'application.framework.logging.CLogger',
	],

	'components' => $components,
];
