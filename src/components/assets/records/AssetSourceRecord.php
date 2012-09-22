<?php
namespace Blocks;

/**
 *
 */
class AssetSourceRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'assetsources';
	}

	public function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::Name, 'required' => true),
			'type'     => array(AttributeType::ClassName, 'required' => true),
			'settings' => AttributeType::Mixed,
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
		);
	}
}
