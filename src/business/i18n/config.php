<?php

return array(
	'sourcePath'    => dirname(__FILE__).'/../../../../',
	'messagePath'   => dirname(__FILE__).'/../../languages',
	'languages'     => array('en_gb','nl','de','fr','ru','es','it'),
	'fileTypes'     => array('php', 'html'),
	'overwrite'     => true,
	'sort'          => true,
	'removeOld'     => true,
	'translator'    => 'Blocks::t,blx.t',
	'exclude'       => array(
		'yiit.php',
		'/blocks/app/framework',
		'/blocks/app/languages',
		'/blocks/app/tests',
		'/blocks/runtime',
		'/webgrind',
	),
);
