<?php
namespace Blocks;

/**
 *
 */
class Block extends BaseModel
{
	protected $tableName = 'blocks';
	protected $settingsTableName = 'blocksettings';
	protected $foreignKeyName = 'block_id';

	protected $hasSettings = true;

	/**
	 * @return array
	 */
	protected $attributes = array(
		'name'         => AttributeType::Name,
		'handle'       => AttributeType::Handle,
		'class'        => AttributeType::ClassName,
		'instructions' => AttributeType::Text
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Site', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('name','site_id'), 'unique' => true),
		array('columns' => array('handle','site_id'), 'unique' => true)
	);
}
