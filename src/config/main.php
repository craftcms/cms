<?php

return CMap::mergeArray(

	require CRAFT_APP_PATH.'etc/config/common.php',

	[
		'basePath'    => CRAFT_APP_PATH,
		'runtimePath' => CRAFT_STORAGE_PATH.'runtime/',
		'name'        => 'Craft',

		// autoloading model and component classes
		'import' => [
			'application.lib.*',
		],
	]
);
