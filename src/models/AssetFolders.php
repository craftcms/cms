<?php

class AssetFolder extends BlocksModel
{
	private static $hasMany = array(
		'assets' => 'Assets.folder'
	);

	private static $belongsTo = array(
		'site' => 'Sites'
	);

	private static $attributes = array(
		'name' => array('type' => 'varchar', 'size' => 1000, 'required' => true),
		'path' => array('type' => 'varchar', 'size' => 1000, 'required' => true)
	);
}
