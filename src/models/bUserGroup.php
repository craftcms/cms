<?php

/**
 *
 */
class bUserGroup extends bBaseModel
{
	protected $tableName = 'usergroups';

	protected $attributes = array(
		'name'        => array('type' => bAttributeType::String, 'required' => true, 'unique' => true),
		'description' => array('type' => bAttributeType::Text)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'bUserGroupBlock', 'foreignKey' => 'group')
	);

	protected $hasMany = array(
		'members'     => array('model' => 'bUserGroupMembers', 'foreignKey' => 'user'),
		'users'       => array('model' => 'bUser', 'through' => 'bUserGroupMembers', 'foreignKey' => array('group'=>'user')),
		'permissions' => array('model' => 'bUserGroupPermission', 'foreignKey' => 'group')
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
