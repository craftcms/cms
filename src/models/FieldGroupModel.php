<?php
namespace Blocks;

/**
 * Field group model class
 */
class FieldGroupModel extends BaseModel
{
	/**
	 * Use the group name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'   => AttributeType::Number,
			'name' => AttributeType::Name,
		);
	}

	/**
	 * Returns the group's fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		return blx()->fields->getFieldsByGroupId($this->id);
	}
}
