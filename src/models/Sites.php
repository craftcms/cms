<?php

class Sites extends BlocksModel
{
	private static $hasSettings = true;
	private static $hasContent = true;
	private static $hasCustomBlocks = true;

	private static $hasMany = array(
		'assetFolders' => 'AssetFolders.site',
		'routes'       => 'Routes.site',
		'sections'     => 'Sections.site'
	);

	private static $attributes = array(
		'handle' => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'url'    => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true)
	);
}
