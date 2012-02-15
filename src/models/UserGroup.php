<?php
namespace Blocks;

/**
 *
 */
class UserGroup extends BaseModel
{
	protected $tableName = 'usergroups';

	protected $attributes = array(
		'name'        => AttributeType::Name,
		'description' => AttributeType::Text
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'UserGroupBlock', 'foreignKey' => 'group')
	);

	protected $hasMany = array(
		'members'     => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'users'       => array('model' => 'User', 'through' => 'UserGroupMember', 'foreignKey' => array('group'=>'user')),
		'permissions' => array('model' => 'UserGroupPermission', 'foreignKey' => 'group')
	);
}
