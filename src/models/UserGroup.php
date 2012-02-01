<?php
namespace Blocks;

/**
 *
 */
class UserGroup extends BaseModel
{
	protected $tableName = 'usergroups';

	protected $attributes = array(
		'name'        => array('type' => AttributeType::String, 'required' => true, 'unique' => true),
		'description' => array('type' => AttributeType::Text)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'UserGroupBlock', 'foreignKey' => 'group')
	);

	protected $hasMany = array(
		'members'     => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'users'       => array('model' => 'User', 'through' => 'UserGroupMembers', 'foreignKey' => array('group'=>'user')),
		'permissions' => array('model' => 'UserGroupPermission', 'foreignKey' => 'group')
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
