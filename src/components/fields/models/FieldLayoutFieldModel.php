<?php
namespace Blocks;

/**
 * Field layout field model class
 */
class FieldLayoutFieldModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'        => AttributeType::Number,
			'fieldId'   => AttributeType::Name,
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * Returns the actual field model.
	 *
	 * @return FieldModel|null
	 */
	public function getField()
	{
		if ($this->fieldId)
		{
			return blx()->fields->getFieldById($this->fieldId);
		}
	}
}
