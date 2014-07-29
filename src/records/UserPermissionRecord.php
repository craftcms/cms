<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Class UserPermissionRecord
 *
 * @package craft.app.records
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
	protected function defineAttributes()
	{
		return array(
			'name' => array(AttributeType::Name, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
		);
	}
}
