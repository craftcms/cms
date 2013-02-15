<?php

$common = require_once(BLOCKS_APP_PATH.'config/common.php');

return CMap::mergeArray(
	$common,

	array(
		'basePath'    => BLOCKS_APP_PATH,
		'runtimePath' => BLOCKS_STORAGE_PATH.'runtime/',
		'name'        => 'Blocks',

		// autoloading model and component classes
		'import' => array(
			'application.lib.*',
			'application.lib.PhpMailer.*',
			'application.lib.Requests.*',
			'application.lib.Requests.Auth.*',
			'application.lib.Requests.Response.*',
			'application.lib.Requests.Transport.*',
			'application.lib.qqFileUploader.*',
		),

		'params' => array(
			'generalConfig'        => $generalConfig,
			'requiredPhpVersion'   => '5.3.0',
			'requiredMysqlVersion' => '5.1.0'
		),
	)
);
