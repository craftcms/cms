<?php

return CMap::mergeArray(
	$commonConfig,

	array(
		'basePath'    => CRAFT_APP_PATH,
		'runtimePath' => CRAFT_STORAGE_PATH.'runtime/',
		'name'        => 'Craft',

		// autoloading model and component classes
		'import' => array(
			'application.lib.*',
		),
	)
);
