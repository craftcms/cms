<?php
namespace Blocks;

/**
 * Asset source model class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetIndexDataModel extends BaseComponentModel
{
	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->uri;
	}

	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		return array(
			'id'		=> AttributeType::Number,
			'sourceId'	=> AttributeType::Number,
			'sessionId' => AttributeType::String,
			'offset'	=> AttributeType::Number,
			'uri'     	=> AttributeType::String,
			'size' 		=> AttributeType::Number,
			'recordId'	=> AttributeType::Number
		);
	}
}
