<?php
namespace Blocks;

/**
 *
 */
class UserGroupPermission extends BaseModel
{
	protected $tableName = 'usergrouppermissions';

	protected $attributes = array(
		'name' => array('type' => AttributeType::Char, 'required' => true),
		'value' => array('type' => AttributeType::TinyInt, 'unsigned' => true, 'required' => true)
	);

	protected $belongsTo = array(
		'group' => array('model' => 'UserGroup', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('group_id', 'name'), 'unique' => true)
	);
}
