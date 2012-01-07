<?php

class Sites extends BlocksModel
{
	protected static $hasSettings = true;
	protected static $hasContent = true;
	protected static $hasCustomBlocks = true;

	protected static $hasMany = array(
		'assetFolders' => 'AssetFolders.site',
		'routes'       => 'Routes.site',
		'sections'     => 'Sections.site'
	);

	protected static $attributes = array(
		'handle' => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'url'    => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true)
	);
}
