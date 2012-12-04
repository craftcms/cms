<?php
namespace Blocks;

/**
 *
 */
class AssetSizeRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetsizes';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'handle' => array(AttributeType::String, 'required' => true),
			'height' => array(AttributeType::Number, 'required' => true),
			'width' => array(AttributeType::Number, 'required' => true)
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}
}
