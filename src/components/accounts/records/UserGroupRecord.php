<?php
namespace Blocks;

/**
 *
 */
class UserGroupRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'usergroups';
	}

	public function defineAttributes()
	{
		return array(
			'name'   => array(AttributeType::Name, 'required' => true),
			'handle' => array(AttributeType::Handle, 'required' => true),
		);
	}

	public function defineRelations()
	{
		return array(
			'users' => array(static::MANY_MANY, 'UserRecord', 'usergroups_users(groupId, userId)'),
		);
	}
}
