<?php
namespace Craft;

/**
 *
 */
class AssetTransformationModel extends BaseModel
{
	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'                  => AttributeType::Number,
			'name'                => AttributeType::String,
			'handle'              => AttributeType::Handle,
			'width'               => AttributeType::Number,
			'height'              => AttributeType::Number,
			'dimensionChangeTime' => AttributeType::DateTime,
			'mode'                => AttributeType::String
		);
	}
}
