<?php
/**
 * This is the configuration for generating language translations for Blocks. It is used by the 'yiic message' command.
 */
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
		'yiilite.php',
		'yiit.php',
		'/blocks/app/framework',
		'/blocks/app/languages',
		'/blocks/app/tests',
		'/blocks/runtime',
		'/webgrind',
	),
);
