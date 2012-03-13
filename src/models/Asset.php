<?php
namespace Blocks;

/**
 *
 */
class Asset extends Model
{
	protected $tableName = 'assets';
	protected $hasBlocks = true;
	protected $hasContent = true;

	protected $attributes = array(
		'path'      => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'required' => true),
		'filename'  => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'required' => true),
		'extension' => array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => false)
	);

	protected $belongsTo = array(
		'folder' => array('model' => 'AssetFolder', 'required' => true)
	);
}
