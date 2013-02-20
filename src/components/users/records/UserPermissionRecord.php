<?php
namespace Blocks;

Blocks::requirePackage(BlocksPackage::Users);

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
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name' => array(AttributeType::Name, 'required' => true, 'unique' => true),
		);
	}
}
