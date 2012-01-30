<?php

/**
 *
 */
class bUserGroupPermission extends bBaseModel
{
	protected $tableName = 'usergrouppermissions';

	protected $attributes = array(
		'name' => array('type' => bAttributeType::Char, 'required' => true),
		'value' => array('type' => bAttributeType::TinyInt, 'unsigned' => true, 'required' => true)
	);

	protected $belongsTo = array(
		'group' => array('model' => 'bUserGroup', 'required' => true)
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
