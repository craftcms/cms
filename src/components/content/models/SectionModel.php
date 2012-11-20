<?php
namespace Blocks;

/**
 * Section model class
 *
 * Used for transporting section data throughout the system.
 */
class SectionModel extends BaseModel
{
	/**
	 * Use the translated section name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Blocks::t($this->name);
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'         => AttributeType::Number,
			'name'       => AttributeType::String,
			'handle'     => AttributeType::String,
			'titleLabel' => AttributeType::String,
			'hasUrls'    => AttributeType::Bool,
			'urlFormat'  => AttributeType::String,
			'template'   => AttributeType::String,
		);
	}
}
