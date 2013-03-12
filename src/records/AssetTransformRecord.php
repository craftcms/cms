<?php
namespace Craft;

/**
 *
 */
class AssetTransformRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assettransforms';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'                => array(AttributeType::String, 'required' => true),
			'handle'              => array(AttributeType::String, 'required' => true),
			'mode'                => array(AttributeType::String, 'required' => true),
			'height'              => AttributeType::Number,
			'width'               => AttributeType::Number,
			'dimensionChangeTime' => AttributeType::DateTime
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
