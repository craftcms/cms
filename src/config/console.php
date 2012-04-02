<?php

return CMap::mergeArray(
	require(BLOCKS_APP_PATH.'config/common.php'),

	array(
		'basePath' => dirname(__FILE__).'/..',

		// autoloading model and component classes
		'import' => array(
			'application.business.*',
			'application.business.Blocks',
			'application.business.services.*',
			'application.migrations.*',
		),

		'commandPath' => dirname(__FILE__).'/../business/console/commands',
	)
);
