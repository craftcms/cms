<?php
namespace Blocks;

/**
 *
 */
class Block extends Model
{
	protected $tableName = 'blocks';
	protected $settingsTableName = 'blocksettings';
	protected $foreignKeyName = 'block_id';
	public $hasSettings = true;

	protected $attributes = array(
		'name'         => AttributeType::Name,
		'handle'       => array('type' => AttributeType::Handle, 'reservedWords' => 'id,date_created,date_updated,uid,title'),
		'class'        => AttributeType::ClassName,
		'instructions' => AttributeType::Text,
		'required'     => AttributeType::Boolean,
		'sort_order'   => AttributeType::SortOrder
	);
}
