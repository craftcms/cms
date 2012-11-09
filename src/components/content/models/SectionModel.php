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

	public function defineAttributes()
	{
		$attributes['id'] = AttributeType::Number;
		$attributes['name'] = AttributeType::String;
		$attributes['handle'] = AttributeType::String;
		$attributes['hasUrls'] = AttributeType::Bool;
		$attributes['urlFormat'] = AttributeType::String;
		$attributes['template'] = AttributeType::String;

		return $attributes;
	}
}
