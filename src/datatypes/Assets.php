<?php

class Assets extends BlocksDataType
{
	static $hasContent = true;
	static $hasCustomBlocks = true;

	static $belongsTo = array(
		'folder' => 'AssetFolders'
	);

	static $attributes = array(
		'path'      => array('type' => AttributeType::String, 'maxSize' => 1000, 'required' => true),
		'filename'  => array('type' => AttributeType::String, 'maxSize' => 1000, 'required' => true),
		'extension' => array('type' => AttributeType::String, 'maxSize' => 50, 'required' => false)
	);
}
