<?php
namespace Craft;

/**
 *
 */
class AssetSourceRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetsources';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'                => array(AttributeType::Name, 'required' => true),
			'type'                => array(AttributeType::ClassName, 'required' => true),
			'settings'            => AttributeType::Mixed,
			'sortOrder'           => AttributeType::SortOrder,
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
