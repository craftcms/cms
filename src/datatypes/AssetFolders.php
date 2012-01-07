<?php

class AssetFolder extends BlocksDataType
{
	static $hasMany = array(
		'assets' => 'Assets.folder'
	);

	static $belongsTo = array(
		'site' => 'Sites'
	);

	static $attributes = array(
		'name' => array('type' => 'varchar', 'size' => 1000, 'required' => true),
		'path' => array('type' => 'varchar', 'size' => 1000, 'required' => true)
	);
}
