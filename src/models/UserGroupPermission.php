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

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
