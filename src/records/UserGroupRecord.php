<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Class UserGroupRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
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
