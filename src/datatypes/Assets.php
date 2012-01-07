<?php

class Assets extends BlocksDataType
{
	private static $hasContent = true;
	private static $hasCustomBlocks = true;

	private static $belongsTo = array(
		'folder' => 'AssetFolders'
	);

	private static $attributes = array(
		'path'      => array('type' => AttributeType::String, 'maxSize' => 1000, 'required' => true),
		'filename'  => array('type' => AttributeType::String, 'maxSize' => 1000, 'required' => true),
		'extension' => array('type' => AttributeType::String, 'maxSize' => 50, 'required' => false)
	);
}
