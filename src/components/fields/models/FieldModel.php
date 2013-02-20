<?php
namespace Blocks;

/**
 * Field model class
 */
class FieldModel extends BaseComponentModel
{
	/**
	 * Use the translated field name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Blocks::t($this->name);
	}

	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'groupId'      => AttributeType::Number,
			'name'         => AttributeType::String,
			'handle'       => AttributeType::String,
			'instructions' => AttributeType::String,
			'required'     => AttributeType::Bool,
			'translatable' => AttributeType::Bool,
		));
	}

	/**
	 * Returns the field's group.
	 *
	 * @return EntryUserModel
	 */
	public function getGroup()
	{
		return blx()->fields->getGroupById($this->groupId);
	}
}
