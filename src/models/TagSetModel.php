<?php
namespace Craft;

/**
 * Tag set model
 */
class TagSetModel extends BaseModel
{
	private $_fieldLayout;

	/**
	 * Use the translated tag set's name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'fieldLayoutId' => AttributeType::Number,
		);
	}

	/**
	 * Returns the section's field layout.
	 *
	 * @return FieldLayoutModel
	 */
	public function getFieldLayout()
	{
		if (!isset($this->_fieldLayout))
		{
			if ($this->fieldLayoutId)
			{
				$this->_fieldLayout = craft()->fields->getLayoutById($this->fieldLayoutId);
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
				$this->_fieldLayout->type = ElementType::Tag;
			}
		}

		return $this->_fieldLayout;
	}

	/**
	 * Sets the section's field layout.
	 *
	 * @param FieldLayoutModel $fieldLayout
	 */
	public function setFieldLayout(FieldLayoutModel $fieldLayout)
	{
		$this->_fieldLayout = $fieldLayout;
	}
}
