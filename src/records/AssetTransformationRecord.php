<?php
namespace Craft;

/**
 *
 */
class AssetTransformationRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assettransformations';
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
			'height'              => array(AttributeType::Number, 'required' => true),
			'width'               => array(AttributeType::Number, 'required' => true),
			'mode'                => array(AttributeType::Enum, 'values' => array('scaleToFit', 'scaleAndCrop', 'stretchToFit'),  'default' => 'scaleToFit'),
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
