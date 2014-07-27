<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Class UserGroupRecord
 *
 * @package craft.app.records
 */
class UserGroupRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'usergroups';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'   => array(AttributeType::Name, 'required' => true),
			'handle' => array(AttributeType::Handle, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'users' => array(static::MANY_MANY, 'UserRecord', 'usergroups_users(groupId, userId)'),
		);
	}
}
