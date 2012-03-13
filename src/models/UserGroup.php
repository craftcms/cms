<?php
namespace Blocks;

/**
 *
 */
class UserGroup extends Model
{
	protected $tableName = 'usergroups';
	protected $hasBlocks = true;

	protected $attributes = array(
		'name'        => AttributeType::Name,
		'description' => AttributeType::Text
	);

	protected $hasMany = array(
		'members'     => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'users'       => array('model' => 'User', 'through' => 'UserGroupMember', 'foreignKey' => array('group'=>'user')),
		'permissions' => array('model' => 'UserGroupPermission', 'foreignKey' => 'group')
	);
}
