<?php

class Assets extends BlocksModel
{
	protected static $hasContent = true;
	protected static $hasCustomBlocks = true;

	protected static $belongsTo = array(
		'folder' => 'AssetFolders'
	);

	protected static $attributes = array(
		'path'      => array('type' => AttributeType::String, 'maxSize' => 1000, 'required' => true),
		'filename'  => array('type' => AttributeType::String, 'maxSize' => 1000, 'required' => true),
		'extension' => array('type' => AttributeType::String, 'maxSize' => 50, 'required' => false)
	);
}
