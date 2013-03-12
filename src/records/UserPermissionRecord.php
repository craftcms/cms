<?php
namespace Craft;

Craft::requirePackage(CraftPackage::Users);

/**
 *
 */
class UserPermissionRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'userpermissions';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name' => array(AttributeType::Name, 'required' => true, 'unique' => true),
		);
	}
}
