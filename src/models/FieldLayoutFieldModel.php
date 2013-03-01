<?php
namespace Craft;

/**
 * Field layout field model class
 */
class FieldLayoutFieldModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
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
			return craft()->fields->getFieldById($this->fieldId);
		}
	}
}
