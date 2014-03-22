<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 *
 */
class UserGroup_UserRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'usergroups_users';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'group' => array(static::BELONGS_TO, 'UserGroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'user'  => array(static::BELONGS_TO, 'UserRecord',      'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('groupId', 'userId'), 'unique' => true),
		);
	}
}
