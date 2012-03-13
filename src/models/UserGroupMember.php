<?php
namespace Blocks;

/**
 *
 */
class UserGroupMember extends Model
{
	protected $tableName = 'usergroupmembers';

	protected $belongsTo = array(
		'user'  => array('model' => 'User', 'required' => true),
		'group' => array('model' => 'UserGroup', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('user_id', 'group_id'), 'unique' => true)
	);
}
