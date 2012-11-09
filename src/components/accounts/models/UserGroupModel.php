<?php
namespace Blocks;

/**
 * User group model class
 *
 * Used for transporting user group data throughout the system.
 */
class UserGroupModel extends BaseModel
{
	/**
	 * Use the translated group name as the string representation.
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
		$attributes['id'] = AttributeType::Number;
		$attributes['name'] = AttributeType::String;
		$attributes['handle'] = AttributeType::String;

		return $attributes;
	}
}
