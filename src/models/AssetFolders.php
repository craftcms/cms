<?php

class AssetFolders extends BlocksModel
{
	protected static $hasMany = array(
		'assets' => 'Assets.folder'
	);

	protected static $belongsTo = array(
		'site' => 'Sites'
	);

	protected static $attributes = array(
		'name' => array('type' => 'varchar', 'size' => 1000, 'required' => true),
		'path' => array('type' => 'varchar', 'size' => 1000, 'required' => true)
	);
}
